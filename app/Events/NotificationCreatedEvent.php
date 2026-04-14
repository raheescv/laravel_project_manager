<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $title,
        public string $content,
        public ?string $link = null,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('user-notification-channel-'.$this->userId);
    }

    public function broadcastAs(): string
    {
        return 'user-notification-event-'.$this->userId;
    }

    public function broadcastWith(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'link' => $this->link,
        ];
    }
}
