<?php

namespace App\Console\Commands;

use App\Models\Configuration;
use App\Models\SaleDaySession;
use App\Models\Tenant;
use App\Services\TenantService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The closing half of "Auto Close Day Sessions": a session somebody forgot to
 * close is closed for them shortly after midnight, with the closing amount set
 * to the expected amount.
 *
 * It only ever closes a session that belongs to an EARLIER day. The scheduler
 * runs this every five minutes for the first hour of the day, so a branch that
 * opens its new day at 00:10 - a normal thing for a late-closing shop - must
 * not be shut down again at 00:15.
 */
class CloseSaleDaySessionsCommand extends Command
{
    public const SETTING_KEY = 'auto_close_day_sessions_enabled';

    protected $signature = 'sale-day-sessions:close-daily';

    protected $description = 'Close sale day sessions left open from an earlier day, setting closing amount to expected amount';

    public function handle(TenantService $tenantService): int
    {
        // This runs outside a request, so there is no tenant to scope by. Read
        // the switch across every tenant, then pin each one before touching its
        // sessions - one tenant's setting must never close another's day.
        $tenantIds = Configuration::withoutTenant()
            ->where('key', self::SETTING_KEY)
            ->where('value', 'yes')
            ->pluck('tenant_id')
            ->filter()
            ->unique();

        if ($tenantIds->isEmpty()) {
            $this->info('Auto-close day sessions is not enabled for any tenant.');

            return self::SUCCESS;
        }

        // One clock reading for the whole run, so a tick that straddles midnight
        // cannot treat "today" differently from one tenant to the next.
        $today = now()->startOfDay();
        $closedCount = 0;
        $failedCount = 0;

        foreach ($tenantIds as $tenantId) {
            $tenant = Tenant::find($tenantId);

            if (! $tenant || ! $tenant->is_active) {
                continue;
            }

            $tenantService->setCurrentTenant($tenant);

            $openSessions = $this->staleSessions($tenantId, $today);

            if ($openSessions->isEmpty()) {
                $this->line("· {$tenant->name}: no session left open from an earlier day.");

                continue;
            }

            $this->info("{$tenant->name}: {$openSessions->count()} session(s) to close.");

            foreach ($openSessions as $session) {
                try {
                    $this->closeSession($session);

                    $closedCount++;
                    $this->info("✓ Closed session ID {$session->id} for branch ID {$session->branch_id} (Expected: {$session->expected_amount})");
                } catch (Exception $e) {
                    $failedCount++;
                    $this->error("✗ Failed to close session ID {$session->id} for branch ID {$session->branch_id}: {$e->getMessage()}");
                }
            }
        }

        $this->info("\nSummary:");
        $this->info("  Closed: {$closedCount}");
        $this->info("  Failed: {$failedCount}");

        return $failedCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Sessions still open from a day before today, for the pinned tenant.
     *
     * The branch scopes are dropped on purpose - there is no user here, so
     * every branch of the tenant is in scope - but the tenant is pinned by
     * hand, because withoutGlobalScopes() drops TenantScope with them.
     */
    protected function staleSessions(int $tenantId, Carbon $today)
    {
        return SaleDaySession::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->open()
            ->where('opened_at', '<', $today->toDateTimeString())
            ->with('sales')
            ->orderBy('id')
            ->get();
    }

    /** Close one session at its expected amount. */
    protected function closeSession(SaleDaySession $session): void
    {
        DB::transaction(function () use ($session) {
            // Expected amount: opening_amount + completed sales (the sales()
            // relationship already filters those). close() recomputes it, and
            // adds tailoring payments on top.
            $expectedAmount = $session->opening_amount + $session->sales->sum('paid');

            $session->close(
                $expectedAmount, // closing_amount = expected_amount
                0, // sync_amount (nothing to reconcile on an unattended close)
                null, // closed_by: nobody closed this, the scheduler did
                'Auto-closed by daily scheduled command',
            );
        });
    }
}
