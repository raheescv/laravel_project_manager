<?php

namespace App\Actions\V1\Product;

use App\Http\Requests\V1\GetProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class GetProductAction
{
    /**
     * Execute the action to get a single product.
     */
    public function execute(GetProductRequest $request): ProductResource
    {
        $filters = $request->validatedWithDefaults();
        $link = $filters['link'] ?? null;
        unset($filters['link']);

        $query = Product::query()->where(array_filter($filters, fn ($value): bool => $value !== null))->withOnlinePrice();
        $product = $link === null ? $query->first() : $this->findByLink($query, (string) $link);
        if (! $product) {
            throw new \Exception('Product not found');
        }
        $product->load([
            'unit:id,name,code',
            'brand:id,name,image_path',
            'department:id,name',
            'mainCategory:id,name',
            'subCategory:id,name',
            'images:id,product_id,path,method',
            'inventories.branch:id,name,location',
        ]);

        return new ProductResource($product);
    }

    /**
     * /products/{link}: a product name or its slug (the showcase), else an id (the apps).
     *
     * @param  Builder<Product>  $query
     */
    private function findByLink(Builder $query, string $link): ?Product
    {
        return Product::findByStorefrontLink($link, clone $query)
            ?? (ctype_digit($link) ? $query->where('products.id', $link)->first() : null);
    }
}
