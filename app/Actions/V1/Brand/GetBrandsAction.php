<?php

namespace App\Actions\V1\Brand;

use App\Models\Brand;
use App\Http\Requests\V1\GetBrandsRequest;

class GetBrandsAction
{
    /**
     * Execute the action to get all brands.
     */
    public function execute(GetBrandsRequest $request): array
    {
        $filters = $request->validatedWithDefaults();
        $availableProductsOnly = $filters['available_products_only'] ?? true;

        $brands = Brand::query()
            ->when($filters['query'] ?? null, function ($query, $value) {
                return $query->where('name', 'like', "%{$value}%");
            })
            ->withCount([
                'products' => function ($query) use ($filters) {
                    if ($filters['size'] ?? null) {
                        $query->where('size', $filters['size']);
                    }
                }
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'image_path']);

        return $brands
            ->filter(function ($brand) use ($availableProductsOnly) {
                return !$availableProductsOnly || $brand->products_count > 0;
            })
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'image_path' => $brand->image_path ? url('storage/' . $brand->image_path) : null,
                    'product_count' => $brand->products_count,
                ];
            })
            ->values()
            ->toArray();
    }
}
