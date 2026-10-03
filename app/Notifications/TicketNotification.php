<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $content,
        protected string $link,
        protected int $ticketId,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'link' => $this->link,
            'page' => 'Ticket',
            'model' => 'Ticket',
            'model_id' => $this->ticketId,
        ];
    }
}
