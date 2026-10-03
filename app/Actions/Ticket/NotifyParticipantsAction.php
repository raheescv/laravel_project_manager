<?php

namespace App\Actions\Ticket;

use App\Jobs\TicketNotificationJob;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Builds the bell notification for a ticket event and queues it for the
 * ticket's participants. The link reopens the ticket's detail on the console.
 * A failed notification never fails the change that triggered it.
 */
class NotifyParticipantsAction
{
    public function commented(Ticket $ticket, int $actorId, string $comment): void
    {
        $this->send($ticket, $actorId, "New comment on #{$ticket->id}", $this->actorName($actorId).': '.Str::limit(trim($comment), 140));
    }

    public function statusChanged(Ticket $ticket, int $actorId): void
    {
        $status = Ticket::statuses()[$ticket->status] ?? $ticket->status;

        $this->send($ticket, $actorId, "#{$ticket->id} moved to {$status}", $this->actorName($actorId)." moved \"{$ticket->title}\" to {$status}.");
    }

    public function edited(Ticket $ticket, int $actorId): void
    {
        $this->send($ticket, $actorId, "#{$ticket->id} was updated", $this->actorName($actorId)." updated \"{$ticket->title}\".");
    }

    private function send(Ticket $ticket, int $actorId, string $title, string $content): void
    {
        rescue(function () use ($ticket, $actorId, $title, $content): void {
            TicketNotificationJob::dispatch(
                $ticket->tenant_id,
                $ticket->id,
                $actorId,
                $title,
                $content,
                route('ticket::index', ['ticket' => $ticket->id]),
            );
        });
    }

    private function actorName(int $actorId): string
    {
        return User::query()->whereKey($actorId)->value('name') ?? 'Someone';
    }
}
