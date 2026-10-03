<?php

namespace App\Actions\Ticket;

use App\Events\TicketActivity;
use App\Services\TenantService;

/**
 * Tells every other open ticket console of the current tenant to refresh.
 * Sent after the response so the actor never waits on the socket server, and
 * skipped for the actor's own socket (X-Socket-ID) — that screen already knows.
 * A broadcast failure never fails the change that triggered it.
 */
class BroadcastActivityAction
{
    public function execute(string $action, ?int $ticketId = null): void
    {
        $tenantId = app(TenantService::class)->getCurrentTenantId();
        if (! $tenantId) {
            return;
        }

        defer(fn () => rescue(fn () => broadcast(new TicketActivity($tenantId, $action, $ticketId))->toOthers()));
    }
}
