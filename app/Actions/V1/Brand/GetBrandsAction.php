<?php

namespace App\Actions\V1\Brand;

use App\Models\Brand;
use Illuminate\Support\Collection;

class GetBrandsAction
{
    /**
     * Execute the action to get all brands.
     */
    public function execute(Collection $filters): array
    {
        $availableProductsOnly = $filters->get('available_products_only', true);
        // Cast to boolean if string 'true'/'false' is passed (e.g. from query string without validation)
        $availableProductsOnly = filter_var($availableProductsOnly, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

        $brands = Brand::query()
            ->when($filters->get('query'), function ($query, $value) {
                return $query->where('name', 'like', "%{$value}%");
            })
            ->withCount([
                'products' => function ($query) use ($filters, $availableProductsOnly) {
                    $query->when($filters->get('size'), fn ($q, $v) => $q->where('size', $v))
                        ->when($filters->get('main_category_id'), fn ($q, $v) => $q->where('main_category_id', $v))
                        ->when($filters->get('sub_category_id'), fn ($q, $v) => $q->where('sub_category_id', $v));

                    // When availableProductsOnly is true, only count products with inventory stock
                    if ($availableProductsOnly) {
                        $query->whereHas('inventories', function ($invQ) {
                            $invQ->where('quantity', '>', 0);
                        });
                    }
                },
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'image_path']);

        return $brands
            ->filter(function ($brand) use ($availableProductsOnly) {
                return ! $availableProductsOnly || $brand->products_count > 0;
            })
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
