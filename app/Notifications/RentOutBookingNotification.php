<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RentOutBookingNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $content,
        protected ?string $link = null,
        protected ?int $modelId = null,
    ) {}

    public function via($notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'link' => $this->link,
            'page' => 'RentOut',
            'model' => 'RentOut',
            'model_id' => $this->modelId,
        ];
    }
}
