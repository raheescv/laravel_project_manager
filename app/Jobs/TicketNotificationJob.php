<?php

namespace App\Jobs;

use App\Events\NotificationCreatedEvent;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a ticket's participants — the reporter and everyone who has commented —
 * that something happened on it. The person who acted is never notified.
 *
 * The tenant is passed explicitly — a queue worker has no request to resolve it from.
 */
class TicketNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $tenantId,
        protected int $ticketId,
        protected int $actorId,
        protected string $title,
        protected string $content,
        protected string $link,
    ) {}

    public function handle(): void
    {
        $ticket = Ticket::withoutGlobalScopes()->where('tenant_id', $this->tenantId)->find($this->ticketId);

        if (! $ticket) {
            return;
        }

        $participantIds = TicketComment::withoutGlobalScopes()
            ->where('ticket_id', $ticket->id)
            ->pluck('created_by')
            ->push($ticket->created_by)
            ->unique()
            ->reject(fn (int $id): bool => $id === $this->actorId)
            ->values();

        if ($participantIds->isEmpty()) {
            return;
        }

        $recipients = User::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->whereIn('id', $participantIds)
            ->active()
            ->get()
            ->filter(fn (User $user): bool => $user->is_admin || $user->can('ticket.view'));

        foreach ($recipients as $user) {
            $user->notify(new TicketNotification($this->title, $this->content, $this->link, $ticket->id));

            if ($user->is_browser_notification_enabled) {
                event(new NotificationCreatedEvent(
                    userId: $user->id,
                    title: $this->title,
                    content: $this->content,
                    link: $this->link,
                ));
            }
        }
    }
}
