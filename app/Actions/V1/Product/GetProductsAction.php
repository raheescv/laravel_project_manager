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

        $query = Product::query()
            ->with([
                'unit:id,name,code',
                'brand:id,name',
                'department:id,name',
                'mainCategory:id,name',
                'subCategory:id,name',
                'inventories.branch:id,name',
            ]);

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
            ->when(! empty($filters['main_category_id']), function ($q) use ($filters) {
                return $q->where('main_category_id', $filters['main_category_id']);
            })
            ->when(! empty($filters['sub_category_id']), function ($q) use ($filters) {
                return $q->where('sub_category_id', $filters['sub_category_id']);
            })
            // Brand filter
            ->when(! empty($filters['brand_id']), function ($q) use ($filters) {
                return $q->where('brand_id', $filters['brand_id']);
            })
            // Size filter
            ->when(! empty($filters['size']), function ($q) use ($filters) {
                return $q->where('size', 'like', "%{$filters['size']}%");
            })
            // Color filter
            ->when(! empty($filters['color']), function ($q) use ($filters) {
                return $q->where('color', 'like', "%{$filters['color']}%");
            })
            // Type filter
            ->when(! empty($filters['type']), function ($q) use ($filters) {
                return $q->where('type', $filters['type']);
            })
            // Price range filters
            ->when(! empty($filters['min_price']), function ($q) use ($filters) {
                return $q->where('mrp', '>=', $filters['min_price']);
            })
            ->when(! empty($filters['max_price']), function ($q) use ($filters) {
                return $q->where('mrp', '<=', $filters['max_price']);
            })
            // General search filter
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $searchTerm = $filters['search'];
                return $q->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('code', 'like', "%{$searchTerm}%")
                        ->orWhere('barcode', 'like', "%{$searchTerm}%")
                        ->orWhere('description', 'like', "%{$searchTerm}%")
                        ->orWhere('color', 'like', "%{$searchTerm}%")
                        ->orWhere('size', 'like', "%{$searchTerm}%")
                        ->orWhere('model', 'like', "%{$searchTerm}%");
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
