<?php

namespace App\Actions\V1\Product;

use App\Models\Product;

class GetAction
{
    /**
     * Retrieve detailed information for a specific product.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $productId): Product
    {
        $branchId = auth()->user()?->default_branch_id;

        return Product::query()
            ->product()
            ->with(['unit:id,name', 'mainCategory:id,name', 'brand:id,name'])
            ->withSum(['inventories' => fn ($query) => $query->where('branch_id', $branchId)], 'quantity')
            ->findOrFail($productId);
    }
}
