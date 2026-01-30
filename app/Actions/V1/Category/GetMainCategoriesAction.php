<?php

namespace App\Actions\V1\Category;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

class GetMainCategoriesAction
{
    /**
     * Execute the action to get main categories (parent categories).
     */
    public function execute(Collection $filters): array
    {
        $categories = Category::withCount('products')->whereNull('parent_id')
            ->where('online_visibility_flag', true)
            ->when($filters->get('query'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name']);

        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'product_count' => Product::where('main_category_id', $category->id)->count(),
            ];
        })->toArray();
    }
}
