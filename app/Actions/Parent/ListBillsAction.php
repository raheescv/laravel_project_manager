<?php

namespace App\Actions\Parent;

use App\Models\Sale;
use App\Models\Scopes\AssignedBranchScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * A student's bills: their completed purchases, newest first, from every canteen.
 *
 * Takes an account the caller has already resolved through FindStudentAction.
 * The branch scope is removed on purpose — it narrows to a STAFF user's branches
 * and would hide bills from parents whenever a staff session shares the browser.
 */
class ListBillsAction
{
    public function execute(int $accountId, ?string $from = null, ?string $to = null, int $perPage = 15): LengthAwarePaginator
    {
        return Sale::withoutGlobalScope(AssignedBranchScope::class)
            ->with('branch:id,name')
            ->withCount('items')
            ->where('account_id', $accountId)
            ->where('status', 'completed')
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($to, fn ($q) => $q->where('date', '<=', $to))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage, ['id', 'branch_id', 'invoice_no', 'date', 'grand_total', 'paid', 'payment_method_name', 'created_at']);
    }
}
