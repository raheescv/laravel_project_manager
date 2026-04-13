<?php

namespace App\Jobs;

use App\Events\NotificationCreatedEvent;
use App\Models\User;
use App\Notifications\RentOutBookingNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RentOutNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $title,
        protected string $content,
        protected ?string $link = null,
        protected ?int $modelId = null,
        protected ?int $excludedUserId = null,
    ) {}

    public function handle(): void
    {
        $users = User::where('is_admin', true)
            ->where('is_browser_notification_enabled', true)
            ->when($this->excludedUserId, fn ($q) => $q->where('id', '!=', $this->excludedUserId))
            ->get(['id', 'tenant_id']);

        foreach ($users as $user) {
            $user->notify(new RentOutBookingNotification(
                $this->title,
                $this->content,
                $this->link,
                $this->modelId,
            ));

            event(new NotificationCreatedEvent(
                userId: $user->id,
                title: $this->title,
                content: $this->content,
                link: $this->link,
            ));
        }
    }
}
