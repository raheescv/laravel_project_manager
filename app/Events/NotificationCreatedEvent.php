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

    public const KIND_ONLINE_ORDER = 'online_order';

    /**
     * @param  string|null  $kind  what the browser should treat it as — an online order chimes and stays on screen
     * @param  array<string, mixed>  $meta  extra detail shown on the live alert
     */
    public function __construct(
        public int $userId,
        public string $title,
        public string $content,
        public ?string $link = null,
        public ?string $kind = null,
        public array $meta = [],
    ) {}

    /**
     * The user's live-alert channel. A public Pusher channel named after the bare user
     * id is shared by every environment on the same Pusher app (local, tests, live), so
     * user 3 everywhere received user 3's alerts from everywhere. Keyed on APP_KEY it is
     * unique per environment and cannot be guessed from the page source of another user.
     */
    public static function channelFor(int $userId): string
    {
        return 'user-notification-'.substr(hash_hmac('sha256', 'user-notification:'.$userId, (string) config('app.key')), 0, 40);
    }

    public function broadcastOn(): Channel
    {
        return new Channel(self::channelFor($this->userId));
    }

    public function broadcastAs(): string
    {
        return 'user-notification-event';
    }

    /**
     * @return array{title: string, content: string, link: string|null, kind: string|null, meta: array<string, mixed>}
     */
    public function broadcastWith(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'link' => $this->link,
            'kind' => $this->kind,
            'meta' => $this->meta,
        ];
    }
}
