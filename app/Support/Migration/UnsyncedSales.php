<?php

namespace App\Support\Migration;

use App\Models\Sale;
use App\Models\Scopes\AssignedBranchScope;
use Illuminate\Support\Collection;

/**
 * Tells which mysql2 sales still have to be replayed into the target tenant.
 *
 * A source sale counts as synced once its invoice_no exists in the target sales table. Soft-deleted
 * target sales count too: (tenant_id, invoice_no) is unique across trashed rows, so replaying one
 * would only fail on the unique index. Tenant-scoped through the pinned tenant; the assigned-branch
 * scope is dropped so a logged-in caller's branch list can't hide sales from the check.
 */
class UnsyncedSales
{
    /**
     * @param  iterable<string>  $invoiceNos
     * @return Collection<string, int> invoice_no => index, for O(1) has() lookups.
     */
    public static function existingInvoiceNos(iterable $invoiceNos): Collection
    {
        return collect($invoiceNos)
            ->filter()
            ->unique()
            ->chunk(1000)
            ->flatMap(fn (Collection $chunk) => Sale::withTrashed()
                ->withoutGlobalScope(AssignedBranchScope::class)
                ->whereIn('invoice_no', $chunk->values()->all())
                ->pluck('invoice_no'))
            ->flip();
    }

    /**
     * Source sales ids whose invoice_no is not in the target yet. A source row without an
     * invoice_no can't be matched, so it is always treated as unsynced.
     *
     * @param  Collection<int, object{id: int, invoice_no: ?string}>  $sourceSales
     * @return Collection<int, int>
     */
    public static function ids(Collection $sourceSales): Collection
    {
        $existing = self::existingInvoiceNos($sourceSales->pluck('invoice_no'));

        return $sourceSales
            ->reject(fn ($sale) => $sale->invoice_no !== null && $sale->invoice_no !== '' && $existing->has($sale->invoice_no))
            ->pluck('id')
            ->values();
    }
}
