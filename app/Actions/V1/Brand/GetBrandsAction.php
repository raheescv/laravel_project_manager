<?php

namespace App\Actions\V1\Brand;

use App\Models\Brand;
use App\Models\Product;
use App\Http\Requests\V1\GetBrandsRequest;

class GetBrandsAction
{
    /**
     * Execute the action to get all brands.
     */
    public function execute(GetBrandsRequest $request): array
    {
        $filters = $request->validatedWithDefaults();
        $brands = Brand::orderBy('name')
            ->when($filters['query'] ?? null, function ($q, $value) {
                return $q->where('name', 'like', "%{$value}%");
            })
            ->get(['id', 'name', 'image_path']);

        return $brands
            ->map(function ($brand) use ($filters) {
                $productCount = Product::where('brand_id', $brand->id)
                    ->when($filters['size'] ?? null, function ($q, $value) {
                        return $q->where('size', $value);
                    })
                    ->count();

                if ($filters['available_products_only'] && !$productCount) {
                    return false;
                }
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'image_path' => $brand->image_path ? url('storage/' . $brand->image_path) : null,
                    'product_count' => $productCount,
                ];
            })
            ->toArray();
    }
}
