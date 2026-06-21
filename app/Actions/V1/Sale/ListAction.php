<?php

namespace App\Actions\V1\Sale;

use App\Http\Requests\V1\Sale\IndexRequest;
use App\Http\Resources\V1\Sale\SaleListResource;
use App\Models\Sale;

class ListAction
{
    /**
     * List sales with filtering and pagination. Sales are auto-scoped
     * to the authenticated user's assigned branches via AssignedBranchScope.
     */
    public function execute(IndexRequest $request): array
    {
        $filters = $request->validatedWithDefaults();
        $user = $request->user();

        $query = Sale::query()
            ->with([
                'account:id,name,mobile',
                'createdUser:id,name',
                'branch:id,name',
            ])
            ->withCount('items')
            ->filter($filters);

        if (! empty($filters['mine_only']) && $user) {
            $query->where('created_by', $user->id);
        }

        $sortBy = $filters['sort_by'];
        $sortDirection = $filters['sort_direction'];
        $query->orderBy($sortBy, $sortDirection);

        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        // Totals across the whole filtered set (not just the current page), so the
        // app can show an accurate "N invoices · total" result line.
        $summary = Sale::query()
            ->filter($filters)
            ->when(! empty($filters['mine_only']) && $user, fn ($q) => $q->where('created_by', $user->id))
            ->selectRaw('COUNT(*) as invoices, COALESCE(SUM(paid), 0) as total_paid')
            ->first();

        $sales = $query->paginate($filters['per_page']);

        return [
            'data' => SaleListResource::collection($sales->items()),
            'summary' => [
                'invoices' => (int) $summary->invoices,
                'total_paid' => round((float) $summary->total_paid, 2),
            ],
            'pagination' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
                'from' => $sales->firstItem(),
                'to' => $sales->lastItem(),
                'has_more_pages' => $sales->hasMorePages(),
            ],
            'filters_applied' => array_filter($filters, function ($value, $key) {
                return ! in_array($key, ['sort_by', 'sort_direction', 'per_page', 'page']) && $value !== null && $value !== '' && $value !== false;
            }, ARRAY_FILTER_USE_BOTH),
        ];
    }
}
