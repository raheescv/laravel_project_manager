<?php

namespace App\Actions\V1\Product;

use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use App\Http\Requests\V1\GetProductRequest;

class GetProductAction
{
    /**
     * Execute the action to get a single product.
     */
    public function execute(GetProductRequest $request): ProductResource
    {
        $filters = $request->validatedWithDefaults();
        $product = Product::query()->where($filters)->first();
        $product->load([
            'unit:id,name,code',
            'brand:id,name',
            'department:id,name',
            'mainCategory:id,name',
            'subCategory:id,name',
            'images:id,product_id,path',
            'inventories.branch:id,name',
        ]);

        return new ProductResource($product);
    }
}
