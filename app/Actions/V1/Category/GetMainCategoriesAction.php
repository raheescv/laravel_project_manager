<?php

namespace App\Actions\V1\Category;

use App\Models\Category;
use Illuminate\Support\Collection;

class GetMainCategoriesAction
{
    /**
     * Execute the action to get main categories (parent categories).
     *
     * The showcase funnel asks for a size before a category, so the counts have
     * to be scoped the same way `/brands` and `/sizes` are: a category offering
     * "1,491 products" that holds nothing in the chosen size — or nothing on the
     * shelf today — walks the customer into an empty grid.
     */
    public function execute(Collection $filters): array
    {
        $type = $filters->get('type');
        $size = $filters->get('size');
        $branchId = $filters->get('branch_id');

        $availableOnly = filter_var(
            $filters->get('available_products_only', false),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE,
        ) ?? false;

        $scope = function ($query) use ($type, $size, $availableOnly, $branchId) {
            $query
                ->when($type, fn ($q, $v) => $q->where('type', $v))
                ->when($size, fn ($q, $v) => $q->where('size', $v))
                ->when($availableOnly, fn ($q) => $q->whereHas('inventories', function ($invQ) use ($branchId) {
                    $invQ->where('quantity', '>', 0)
                        ->when($branchId, fn ($bq, $b) => $bq->where('branch_id', $b));
                }));
        };

        return Category::query()
            ->withCount(['products' => $scope])
            ->whereNull('parent_id')
            ->where('online_visibility_flag', true)
            ->when($filters->get('query'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                // The same figure the `having` filtered on — counting a second
                // time per row was a query per category for an identical answer.
                'product_count' => (int) $category->products_count,
            ])
            ->toArray();
    }
}
