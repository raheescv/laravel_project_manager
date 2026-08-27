<?php

namespace App\Actions\V1\Brand;

use App\Models\Brand;
use Illuminate\Support\Collection;

class GetBrandsAction
{
    /**
     * Execute the action to get all brands.
     *
     * The count is what the customer reads as a promise, so it is scoped the
     * same way the results grid will be: by size, and — when the caller asks
     * for stock only — by the branch they are standing in. Counting stock that
     * sits in another shop walks them to a rail that is shorter than the tile
     * said, or empty.
     */
    public function execute(Collection $filters): array
    {
        $availableProductsOnly = $filters->get('available_products_only', true);
        // Cast to boolean if string 'true'/'false' is passed (e.g. from query string without validation)
        $availableProductsOnly = filter_var($availableProductsOnly, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

        $branchId = $filters->get('branch_id');

        $brands = Brand::query()
            ->when($filters->get('query'), function ($query, $value) {
                return $query->where('name', 'like', "%{$value}%");
            })
            ->withCount([
                'products' => function ($query) use ($filters, $availableProductsOnly, $branchId) {
                    $query->when($filters->get('size'), fn ($q, $v) => $q->where('size', $v))
                        ->when($filters->get('main_category_id'), fn ($q, $v) => $q->where('main_category_id', $v))
                        ->when($filters->get('sub_category_id'), fn ($q, $v) => $q->where('sub_category_id', $v))
                        // Only count products in an online-visible category (matches /categories).
                        ->whereHas('mainCategory', fn ($catQ) => $catQ->where('online_visibility_flag', true));

                    // When availableProductsOnly is true, only count products on
                    // the shelf — of the requested branch when one was named.
                    if ($availableProductsOnly) {
                        $query->whereHas('inventories', function ($invQ) use ($branchId) {
                            $invQ->where('quantity', '>', 0)
                                ->when($branchId, fn ($bq, $b) => $bq->where('branch_id', $b));
                        });
                    }
                },
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'image_path']);

        return $brands
            // A tile reading "0" is a dead end whichever way the stock filter is
            // set — the count is already scoped by size, so zero means there is
            // nothing behind it.
            ->filter(fn ($brand) => $brand->products_count > 0)
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'image_path' => $brand->image_path ? url('storage/'.$brand->image_path) : null,
                    'product_count' => $brand->products_count,
                ];
            })
            ->values()
            ->toArray();
    }
}
