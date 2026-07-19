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
        $type = $filters->get('type');

        $categories = Category::withCount(['products' => function ($query) use ($type) {
            $query->when($type, fn ($q) => $q->where('type', $type));
        }])->whereNull('parent_id')
            ->where('online_visibility_flag', true)
            ->when($filters->get('query'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name']);

        return $categories->map(function ($category) use ($type) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'product_count' => Product::where('main_category_id', $category->id)
                    ->when($type, fn ($q) => $q->where('type', $type))
                    ->count(),
            ];
        })->toArray();
    }
}
