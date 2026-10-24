<?php

namespace App\Actions\Sale;

use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;

/**
 * Stamps a sale with its biller's own running bill number, printed on the
 * "Office Bill" receipt as `{users.code}/{n}`: n is how many sales that biller
 * (sales.created_by) has rung up on the sale's date — the day session's date —
 * so it restarts at 1 each business day. The number is written once and kept,
 * so reprints, edits and later deletions never change it.
 */
class AssignStaffBillNoAction
{
    public function execute(Sale $sale): void
    {
        if ($sale->staff_bill_sequence || ! $sale->created_by || ! $sale->date) {
            return;
        }

        $date = Carbon::parse($sale->date)->toDateString();

        // Holding the biller's row serialises two tills ringing up for the same
        // biller at once, so they can never draw the same number.
        $biller = User::withoutGlobalScopes()->whereKey($sale->created_by)->lockForUpdate()->first(['id', 'code']);

        $sequence = (int) Sale::withoutGlobalScopes()
            ->withTrashed()
            ->where('tenant_id', $sale->tenant_id)
            ->where('created_by', $sale->created_by)
            ->where('date', $date)
            ->max('staff_bill_sequence') + 1;

        $sale->staff_bill_sequence = $sequence;
        $sale->bill_no = (filled($biller?->code) ? $biller->code : $sale->created_by).'/'.$sequence;
    }
}
