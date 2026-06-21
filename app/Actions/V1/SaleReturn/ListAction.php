<?php

namespace App\Actions\V1\SaleReturn;

use App\Http\Requests\V1\SaleReturn\IndexRequest;
use App\Http\Resources\V1\SaleReturn\SaleReturnListResource;
use App\Models\SaleReturn;
use Illuminate\Database\Eloquent\Builder;

class ListAction
{
    /**
     * List sale returns with filtering and pagination. Returns are auto-scoped
     * to the authenticated user's assigned branches via AssignedBranchScope.
     *
     * SaleReturn has no generic `filter` scope (unlike Sale), so the filters are
     * applied here and shared between the page query and the totals query.
     */
    public function execute(IndexRequest $request): array
    {
        $filters = $request->validatedWithDefaults();
        $user = $request->user();
        $userId = $user?->id;

        $query = SaleReturn::query()
            ->with([
                'account:id,name,mobile',
                'createdUser:id,name',
                'branch:id,name',
                'payments.paymentMethod:id,name',
            ])
            ->withCount('items');

        $this->applyFilters($query, $filters, $userId);

        $sortBy = $filters['sort_by'];
        $sortDirection = $filters['sort_direction'];
        $query->orderBy($sortBy, $sortDirection);

        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        // Totals across the whole filtered set (not just the current page), so the
        // app can show an accurate "N returns · total" result line.
        $summaryQuery = SaleReturn::query();
        $this->applyFilters($summaryQuery, $filters, $userId);
        $summary = $summaryQuery
            ->selectRaw('COUNT(*) as returns, COALESCE(SUM(paid), 0) as total_paid')
            ->first();

        $returns = $query->paginate($filters['per_page']);

        return [
            'data' => SaleReturnListResource::collection($returns->items()),
            'summary' => [
                'invoices' => (int) $summary->returns,
                'total_paid' => round((float) $summary->total_paid, 2),
            ],
            'pagination' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
                'from' => $returns->firstItem(),
                'to' => $returns->lastItem(),
                'has_more_pages' => $returns->hasMorePages(),
            ],
            'filters_applied' => array_filter($filters, function ($value, $key) {
                return ! in_array($key, ['sort_by', 'sort_direction', 'per_page', 'page']) && $value !== null && $value !== '' && $value !== false;
            }, ARRAY_FILTER_USE_BOTH),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, ?int $userId): void
    {
        $query
            ->when($filters['search'] ?? '', function (Builder $q, $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('sale_returns.reference_no', 'like', "%{$search}%")
                        ->orWhereHas('account', fn (Builder $a) => $a->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? '', fn (Builder $q, $value) => $q->where('sale_returns.status', $value))
            ->when($filters['customer_id'] ?? null, fn (Builder $q, $value) => $q->where('sale_returns.account_id', $value))
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $value) => $q->where('sale_returns.branch_id', $value))
            ->when($filters['from_date'] ?? null, fn (Builder $q, $value) => $q->where('sale_returns.date', '>=', $value))
            ->when($filters['to_date'] ?? null, fn (Builder $q, $value) => $q->where('sale_returns.date', '<=', $value))
            ->when($filters['payment_method_id'] ?? null, fn (Builder $q, $value) => $q->whereHas('payments', fn (Builder $p) => $p->where('payment_method_id', $value)))
            ->when(! empty($filters['mine_only']) && $userId, fn (Builder $q) => $q->where('sale_returns.created_by', $userId));
    }
}
