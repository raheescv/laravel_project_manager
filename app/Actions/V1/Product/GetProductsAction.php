<?php

namespace App\Actions\V1\Product;

use App\Http\Requests\V1\GetProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;

class GetProductsAction
{
    /**
     * Execute the action to get products with filtering and pagination.
     */
    public function execute(GetProductRequest $request): array
    {
        $filters = $request->validatedWithDefaults();

        $with = [
            'unit:id,name,code',
            'brand:id,name',
            'department:id,name',
            'mainCategory:id,name',
            'subCategory:id,name',
        ];

        if (! empty($filters['branch_id'])) {
            $branchId = $filters['branch_id'];
            $with['inventories'] = function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->with('branch:id,name');
            };
        } else {
            $with[] = 'inventories.branch:id,name';
        }

        $query = Product::query()->with($with);

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply sorting
        $this->applySorting($query, $filters);

        // Get paginated results
        $perPage = $filters['per_page'];
        $products = $query->paginate($perPage);

        return [
            'data' => ProductResource::collection($products->items()),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
                'has_more_pages' => $products->hasMorePages(),
            ],
            'filters_applied' => array_filter($filters, function ($value, $key) {
                return ! in_array($key, ['sort_by', 'sort_direction', 'per_page', 'page']) && $value !== null;
            }, ARRAY_FILTER_USE_BOTH),
        ];
    }

    /**
     * Apply filters to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyFilters($query, array $filters): void
    {
        $query
            // Category filters
            ->when($filters['main_category_id'] ?? null, function ($q, $value) {
                return $q->where('main_category_id', $value);
            })
            ->when($filters['sub_category_id'] ?? null, function ($q, $value) {
                return $q->where('sub_category_id', $value);
            })
            // Brand filter
            ->when($filters['brand_id'] ?? null, function ($q, $value) {
                return $q->where('brand_id', $value);
            })
            // Inventory branch filter
            ->when($filters['branch_id'] ?? null, function ($q, $value) {
                return $q->whereHas('inventories', function ($invQ) use ($value) {
                    $invQ->where('branch_id', $value);
                });
            })
            // In stock only filter
            ->when($filters['in_stock_only'] ?? false, function ($q) {
                return $q->whereHas('inventories', function ($invQ) {
                    $invQ->where('quantity', '>', 0);
                });
            })
            // Size filter
            ->when($filters['size'] ?? null, function ($q, $value) {
                return $q->where('size', 'like', "%{$value}%");
            })
            // Color filter
            ->when($filters['color'] ?? null, function ($q, $value) {
                return $q->where('color', 'like', "%{$value}%");
            })
            // Price range filters
            ->when($filters['min_price'] ?? null, function ($q, $value) {
                return $q->where('mrp', '>=', $value);
            })
            ->when($filters['max_price'] ?? null, function ($q, $value) {
                return $q->where('mrp', '<=', $value);
            })
            // General search filter
            ->when($filters['search'] ?? null, function ($q, $value) {
                return $q->where(function ($subQuery) use ($value) {
                    $subQuery->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('barcode', 'like', "%{$value}%")
                        ->orWhere('description', 'like', "%{$value}%")
                        ->orWhere('color', 'like', "%{$value}%")
                        ->orWhere('size', 'like', "%{$value}%")
                        ->orWhere('model', 'like', "%{$value}%");
                });
            });
    }

    /**
     * Apply sorting to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applySorting($query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        // Map sort fields to actual database columns
        $sortFieldMap = [
            'name' => 'name',
            'price' => 'mrp',
            'mrp' => 'mrp',
            'cost' => 'cost',
        ];

        $sortField = $sortFieldMap[$sortBy] ?? 'name';
        $query->orderBy($sortField, $sortDirection);

        // Add secondary sort for consistency
        if ($sortField !== 'name') {
            $query->orderBy('name', 'asc');
        }
    }
}
