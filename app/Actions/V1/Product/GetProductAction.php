<?php

namespace App\Actions\V1\Product;

use App\Http\Resources\V1\ProductResource;
use App\Models\Product;

class GetProductAction
{
    /**
     * Execute the action to get a single product.
     */
    public function execute(Product $product): ProductResource
    {
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
