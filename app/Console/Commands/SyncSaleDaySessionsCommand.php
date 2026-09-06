<?php

namespace App\Console\Commands;

use App\Actions\Sale\ChangeDaySessionAction;
use App\Models\Sale;
use App\Models\SaleDaySession;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\TenantService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bulk version of the "Change Sale Day Session" modal. The move itself is the
 * modal's own App\Actions\Sale\ChangeDaySessionAction; this command only decides
 * WHICH session each sale belongs on.
 *
 * Sales are stamped with whatever session happened to be open at the moment they
 * were created, which goes wrong whenever a session is opened late, left open
 * overnight, or a sale is imported/back-dated. This command re-points sales at
 * the session that actually covers their created_at timestamp and carries the
 * session date across to the journals/payments, exactly like the modal does.
 */
class SyncSaleDaySessionsCommand extends Command
{
    protected $signature = 'sale:sync-day-sessions
        {--tenant= : Restrict to a single tenant id}
        {--branch= : Restrict to a single branch id}
        {--sale= : Comma separated sale ids to process}
        {--from= : Sales created on or after this date (Y-m-d)}
        {--to= : Sales created on or before this date (Y-m-d)}
        {--status= : Only sales with this status (draft|completed|cancelled)}
        {--sale-type= : Only sales of this type}
        {--created-by= : Only sales created by this user id}
        {--only-missing : Only sales that have no day session at all}
        {--match=window : How created_at is matched to a session: window|date}
        {--strict : In window mode, do not fall back to a same-day match}
        {--keep-dates : Do not rewrite the sale/journal/payment dates to the session date}
        {--keep-amounts : Do not adjust closing/expected amounts of closed sessions}
        {--chunk=500 : Rows fetched per chunk}
        {--dry-run : Report what would change without writing anything}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Re-assign sales to the sale day session matching their created_at date';

    /** @var array<int, Collection<int, SaleDaySession>> keyed by "tenantId:branchId" */
    protected array $sessionCache = [];

    protected int $updated = 0;

    protected int $alreadyCorrect = 0;

    protected int $unmatched = 0;

    protected int $failed = 0;

    /** @var array<int, array<int, string>> */
    protected array $changes = [];

    public function handle(): int
    {
        $mode = (string) $this->option('match');
        if (! in_array($mode, ['window', 'date'], true)) {
            $this->error('--match must be either "window" or "date".');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $total = $this->baseQuery()->count();
        if ($total === 0) {
            $this->info('No sales matched the given filters. Nothing to do.');

            return self::SUCCESS;
        }

        $this->line('Sales in scope: <info>'.$total.'</info>   Match mode: <info>'.$mode.'</info>'.($dryRun ? '   <comment>(dry run)</comment>' : ''));

        if (! $dryRun && ! $this->option('force') && $this->input->isInteractive()) {
            if (! $this->confirm("Re-assign day sessions for {$total} sale(s)?", false)) {
                $this->warn('Aborted.');

                return self::SUCCESS;
            }
        }

        $tenantIds = $this->baseQuery()->distinct()->pluck('tenant_id')->filter()->values();

        foreach ($tenantIds as $tenantId) {
            // Relation queries below (journals, payments, entries) still carry
            // TenantScope, so pin the tenant context per group instead of
            // fighting the scope on every single query. Without a resolvable
            // tenant those queries would silently run under whatever the
            // TenantService fallback returns, so skip the group entirely.
            if (! $this->bindTenant((int) $tenantId)) {
                $this->warn("Skipped tenant {$tenantId}: tenant record not found.");

                continue;
            }

            $this->baseQuery()
                ->where('sales.tenant_id', $tenantId)
                ->orderBy('sales.id')
                ->chunkById((int) $this->option('chunk'), function (Collection $sales) use ($mode, $dryRun): void {
                    foreach ($sales as $sale) {
                        $this->processSale($sale, $mode, $dryRun);
                    }
                }, 'sales.id', 'id');
        }

        $this->report($dryRun);

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * The date range goes through Sale::scopeFilter() — the same list filter the
     * Sales table and the V1 ListAction use — with `based_on` pinned to
     * created_at, so "sales between these dates" means here exactly what it
     * means on screen.
     *
     * Only the tenant and branch scopes are dropped: there is no authenticated
     * user in the console, so AssignedBranchScope would be a silent no-op and
     * TenantScope could latch onto the TENANT_ID fallback. SoftDeletingScope
     * stays on - a bare withoutGlobalScopes() would drag deleted sales in.
     */
    protected function baseQuery()
    {
        return Sale::query()
            ->withoutGlobalScopes([TenantScope::class, AssignedBranchScope::class])
            ->filter([
                'based_on' => 'created_at',
                'from_date' => $this->option('from'),
                'to_date' => $this->option('to'),
                'branch_id' => $this->option('branch'),
                'status' => $this->option('status'),
                'sale_type' => $this->option('sale-type'),
                'created_by' => $this->option('created-by'),
            ])
            ->whereNotNull('sales.branch_id')
            ->when($this->option('tenant'), fn ($q, $v) => $q->where('sales.tenant_id', $v))
            ->when($this->option('sale'), fn ($q, $v) => $q->whereIn('sales.id', array_filter(array_map('trim', explode(',', $v)))))
            ->when($this->option('only-missing'), fn ($q) => $q->whereNull('sales.sale_day_session_id'));
    }

    protected function bindTenant(int $tenantId): bool
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return false;
        }

        app(TenantService::class)->setCurrentTenant($tenant);

        return true;
    }

    protected function processSale(Sale $sale, string $mode, bool $dryRun): void
    {
        $session = $this->resolveSession($sale, $mode);

        if (! $session) {
            $this->unmatched++;
            $this->record($sale->tenant_id, sprintf(
                'Sale #%d (%s, branch %d, created %s) - no session covers this date',
                $sale->id, $sale->invoice_no, $sale->branch_id, $sale->created_at?->format('d-m-Y H:i')
            ));

            return;
        }

        if ((int) $sale->sale_day_session_id === (int) $session->id) {
            $this->alreadyCorrect++;

            return;
        }

        $this->record($sale->tenant_id, sprintf(
            'Sale #%d (%s, created %s): session %s -> #%d [%s]',
            $sale->id,
            $sale->invoice_no,
            $sale->created_at?->format('d-m-Y H:i'),
            $sale->sale_day_session_id ? '#'.$sale->sale_day_session_id : 'none',
            $session->id,
            $session->opened_at->format('d-m-Y')
        ));

        if ($dryRun) {
            $this->updated++;

            return;
        }

        try {
            DB::transaction(function () use ($sale, $session): void {
                $this->applySession($sale, $session);
            });
            $this->updated++;
        } catch (\Throwable $e) {
            $this->failed++;
            $this->error("Sale #{$sale->id}: {$e->getMessage()}");
        }
    }

    protected function applySession(Sale $sale, SaleDaySession $session): void
    {
        $response = (new ChangeDaySessionAction())->execute($sale, $session, null, [
            'sync_dates' => ! $this->option('keep-dates'),
            'sync_amounts' => ! $this->option('keep-amounts'),
        ]);

        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }

    protected function resolveSession(Sale $sale, string $mode): ?SaleDaySession
    {
        $createdAt = $sale->created_at;
        if (! $createdAt) {
            return null;
        }

        $sessions = $this->sessionsFor((int) $sale->tenant_id, (int) $sale->branch_id);

        if ($mode === 'window') {
            $match = $this->matchByWindow($sessions, $createdAt);
            if ($match || $this->option('strict')) {
                return $match;
            }
        }

        return $this->matchByDate($sessions, $createdAt);
    }

    /** The session that was actually running at that instant. */
    protected function matchByWindow(Collection $sessions, Carbon $createdAt): ?SaleDaySession
    {
        return $sessions
            ->filter(fn (SaleDaySession $s) => $s->opened_at->lte($createdAt)
                && (is_null($s->closed_at) || $s->closed_at->gte($createdAt)))
            ->sortByDesc(fn (SaleDaySession $s) => $s->opened_at->getTimestamp())
            ->first();
    }

    /** The session belonging to that calendar day; the latest one opened at or before the sale. */
    protected function matchByDate(Collection $sessions, Carbon $createdAt): ?SaleDaySession
    {
        $sameDay = $sessions->filter(fn (SaleDaySession $s) => $s->opened_at->isSameDay($createdAt))
            ->sortBy(fn (SaleDaySession $s) => $s->opened_at->getTimestamp())
            ->values();

        if ($sameDay->isEmpty()) {
            return null;
        }

        return $sameDay->last(fn (SaleDaySession $s) => $s->opened_at->lte($createdAt)) ?? $sameDay->first();
    }

    protected function sessionsFor(int $tenantId, int $branchId): Collection
    {
        $key = $tenantId.':'.$branchId;

        return $this->sessionCache[$key] ??= SaleDaySession::withoutGlobalScopes([TenantScope::class, AssignedBranchScope::class])
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->orderBy('opened_at')
            ->get();
    }

    protected function record(?int $tenantId, string $line): void
    {
        $this->changes[(int) $tenantId][] = $line;

        if ($this->output->isVerbose()) {
            $this->line('  '.$line);
        }
    }

    protected function report(bool $dryRun): void
    {
        if (! $this->output->isVerbose()) {
            foreach ($this->changes as $tenantId => $lines) {
                $this->newLine();
                $this->line("Tenant {$tenantId}:");
                foreach (array_slice($lines, 0, 25) as $line) {
                    $this->line('  '.$line);
                }
                if (count($lines) > 25) {
                    $this->line('  ... '.(count($lines) - 25).' more (run with -v to list them all)');
                }
            }
        }

        $this->newLine();
        $this->table(['Result', 'Count'], [
            [$dryRun ? 'Would re-assign' : 'Re-assigned', $this->updated],
            ['Already correct', $this->alreadyCorrect],
            ['No matching session', $this->unmatched],
            ['Failed', $this->failed],
        ]);
    }
}
