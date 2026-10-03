<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Something changed on the ticket console. Every open console of the tenant —
 * on any device — refetches what it shows, so the payload is only a pointer:
 * the data itself is always re-read through the permission-guarded API.
 */
class TicketActivity implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $tenantId,
        public string $action,
        public ?int $ticketId = null,
    ) {}

    public static function channelName(int $tenantId): string
    {
        return "tenant.{$tenantId}.tickets";
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(self::channelName($this->tenantId));
    }

    public function broadcastAs(): string
    {
        return 'ticket.activity';
    }

    /**
     * @return array{action: string, ticket_id: int|null}
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'ticket_id' => $this->ticketId,
        ];
    }
}
