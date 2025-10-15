<?php

namespace App\Actions\V1\Category;

use App\Models\Category;
use App\Models\Product;

class GetMainCategoriesAction
{
    /**
     * Execute the action to get main categories (parent categories).
     */
    public function execute(): array
    {
        $categories = Category::whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        return $categories->map(function ($category) {
            $productCount = Product::where('main_category_id', $category->id)->count();

            return [
                'id' => $category->id,
                'name' => $category->name,
                'product_count' => $productCount,
            ];
        })->toArray();
    }
}
