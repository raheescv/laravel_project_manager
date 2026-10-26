<?php

namespace App\Actions\Sale;

use App\Models\Sale;
use App\Models\User;
use App\Services\TenantService;
use Carbon\Carbon;
use Closure;

/**
 * Stamps a sale with its biller's own running bill number, printed on the
 * "Office Bill" receipt as `{users.code}/{n}`: n is how many sales that biller
 * (sales.created_by) has rung up on the sale's date — the day session's date —
 * so it restarts at 1 each business day. The number is written once and kept,
 * so reprints, edits and later deletions never change it.
 *
 * A till that rang the sale up offline has already printed a number for it —
 * staff keep those slips to count their day — so a sync may arrive claiming one
 * (see {@see self::honouring()}). The claim is kept when it is still free for
 * that biller on that date; otherwise the sale takes the next number as usual.
 */
class AssignStaffBillNoAction
{
    /**
     * The number the device printed for the sale being created right now.
     *
     * @var array{sequence: int, date: string}|null
     */
    private static ?array $claim = null;

    /**
     * Run $callback with the bill number a till already printed for the sale it
     * creates. Scoped to the callback and consumed by the first sale numbered
     * inside it, so the claim can never leak onto an unrelated sale.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public static function honouring(?int $sequence, ?string $date, Closure $callback): mixed
    {
        if (! $sequence || ! $date) {
            return $callback();
        }

        self::$claim = ['sequence' => $sequence, 'date' => Carbon::parse($date)->toDateString()];

        try {
            return $callback();
        } finally {
            self::$claim = null;
        }
    }

    /**
     * The highest number a biller has drawn on a date, 0 when none — what a till
     * counts on from while it cannot reach the server. Defaults to the current
     * tenant.
     */
    public static function lastSequence(int $billerId, string $date, ?int $tenantId = null): int
    {
        return (int) Sale::withoutGlobalScopes()
            ->withTrashed()
            ->where('tenant_id', $tenantId ?? app(TenantService::class)->getCurrentTenantId())
            ->where('created_by', $billerId)
            ->where('date', Carbon::parse($date)->toDateString())
            ->max('staff_bill_sequence');
    }

    public function execute(Sale $sale): void
    {
        if (filled($sale->bill_no) || ! $sale->created_by || ! $sale->date) {
            return;
        }

        $claim = self::$claim;
        self::$claim = null;

        $date = Carbon::parse($sale->date)->toDateString();

        // Holding the biller's row serialises two tills ringing up for the same
        // biller at once, so they can never draw the same number.
        $biller = User::withoutGlobalScopes()->whereKey($sale->created_by)->lockForUpdate()->first(['id', 'code']);

        $sale->staff_bill_sequence = $this->isFree($sale, $claim, $date)
            ? $claim['sequence']
            : self::lastSequence((int) $sale->created_by, $date, (int) $sale->tenant_id) + 1;
        $sale->bill_no = (filled($biller?->code) ? $biller->code : $sale->created_by).'/'.$sale->staff_bill_sequence;
    }

    /**
     * Whether the printed number can stand: it was counted against the business
     * date the sale actually lands on, and no other sale of this biller holds it.
     *
     * @param  array{sequence: int, date: string}|null  $claim
     */
    private function isFree(Sale $sale, ?array $claim, string $date): bool
    {
        if (! $claim || $claim['date'] !== $date) {
            return false;
        }

        return ! Sale::withoutGlobalScopes()
            ->withTrashed()
            ->where('tenant_id', $sale->tenant_id)
            ->where('created_by', $sale->created_by)
            ->where('date', $date)
            ->where('staff_bill_sequence', $claim['sequence'])
            ->exists();
    }
}
