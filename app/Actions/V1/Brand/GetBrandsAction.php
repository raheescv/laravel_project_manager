<?php

namespace App\Actions\V1\Brand;

use App\Models\Brand;
use App\Models\Product;

class GetBrandsAction
{
    /**
     * Execute the action to get all brands.
     */
    public function execute(): array
    {
        $brands = Brand::orderBy('name')
            ->get(['id', 'name','image_path']);

        return $brands->map(function ($brand) {
            $productCount = Product::where('brand_id', $brand->id)->count();

            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'image_path' => $brand->image_path ? url('storage/' . $brand->image_path) : null,
                'product_count' => $productCount,
            ];
        })->toArray();
    }
}
