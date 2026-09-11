<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Configuration;
use App\Models\Holiday;
use App\Models\SaleDaySession;
use App\Models\Tenant;
use App\Models\WorkingDay;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * The opening half of "Auto Close Day Sessions": once a tenant switches on
 * Auto Open Day Sessions (Settings -> Sale Settings), every branch has its day
 * session opened at the opening time kept in Settings -> Working Day, so the
 * first sale of the morning never waits for someone to remember the float
 * screen.
 *
 * Scheduled every minute and safe to run that often. It only acts on a working
 * day that is not a holiday, only once that day's opening time has passed, and
 * only for a branch with no session for today and nothing still open from an
 * earlier day - two open sessions on one branch is exactly what the app
 * forbids. The session is stamped with the configured opening time rather than
 * the minute the scheduler happened to fire, so reports read the same as a
 * hand-opened day.
 */
class OpenSaleDaySessionsCommand extends Command
{
    public const SETTING_KEY = 'auto_open_day_sessions_enabled';

    protected $signature = 'sale-day-sessions:open-daily';

    protected $description = 'Open a sale day session for every branch at the Working Day opening time, for tenants with auto-open enabled';

    public function handle(TenantService $tenantService): int
    {
        // This runs outside a request, so there is no tenant to scope by. Read
        // the switch across every tenant, then pin each one before touching its
        // working week, holidays, branches and sessions - all tenant-scoped.
        $tenantIds = Configuration::withoutTenant()
            ->where('key', self::SETTING_KEY)
            ->where('value', 'yes')
            ->pluck('tenant_id')
            ->filter()
            ->unique();

        if ($tenantIds->isEmpty()) {
            $this->info('Auto-open day sessions is not enabled for any tenant.');

            return self::SUCCESS;
        }

        // One clock reading for the whole run, so a tick that straddles midnight
        // cannot open "today" for one tenant and "tomorrow" for the next.
        $now = now();
        $opened = 0;
        $failed = 0;

        foreach ($tenantIds as $tenantId) {
            $tenant = Tenant::find($tenantId);

            if (! $tenant || ! $tenant->is_active) {
                continue;
            }

            $tenantService->setCurrentTenant($tenant);

            $openingAt = $this->openingMomentFor($now);

            if (! $openingAt) {
                $this->line("· {$tenant->name}: doors stay shut right now — nothing to open.");

                continue;
            }

            foreach (Branch::query()->orderBy('id')->get() as $branch) {
                try {
                    if ($this->openFor($branch, $openingAt)) {
                        $opened++;
                        $this->info("✓ {$tenant->name} / {$branch->name}: opened at {$openingAt->format('d-m-Y H:i')}");
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("✗ {$tenant->name} / {$branch->name}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Day sessions opened: {$opened}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Today's opening moment for the pinned tenant, or null while the doors are
     * shut: a day the working week switches off, a holiday, or an opening time
     * that has not arrived yet.
     *
     * WorkingDay::schedule() resolves the same fallback chain the appointment
     * scheduler uses, so "when do we open" has exactly one answer in the app.
     */
    protected function openingMomentFor(Carbon $now): ?Carbon
    {
        $timing = WorkingDay::schedule()[(int) $now->dayOfWeek] ?? null;

        if (! $timing || Holiday::isOn($now)) {
            return null;
        }

        $openingAt = $now->copy()->setTimeFromTimeString($timing['start_time']);

        return $openingAt->lte($now) ? $openingAt : null;
    }

    /** Open today's session for one branch. False when the branch needs nothing. */
    protected function openFor(Branch $branch, Carbon $openingAt): bool
    {
        // Nothing still open from an earlier day: a branch can only ever have
        // one open session, and auto-close being off (or having failed) is no
        // reason to start a second one underneath it.
        if (SaleDaySession::hasOpenSession($branch->id)) {
            return false;
        }

        // Nothing already opened today, whatever its status: a session somebody
        // closed this afternoon was closed on purpose, and reopening it is the
        // one thing this command must never do.
        $openedToday = SaleDaySession::where('branch_id', $branch->id)
            ->whereDate('opened_at', $openingAt->toDateString())
            ->exists();

        if ($openedToday) {
            return false;
        }

        SaleDaySession::create([
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'opened_by' => null,
            'opened_at' => $openingAt->toDateTimeString(),
            'opening_amount' => 0,
            'status' => 'open',
            'notes' => 'Auto-opened at the Working Day opening time',
        ]);

        return true;
    }
}
