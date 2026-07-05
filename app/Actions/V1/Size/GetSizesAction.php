<?php

namespace App\Actions\V1\Size;

use App\Http\Requests\V1\GetSizesRequest;
use App\Models\Product;

class GetSizesAction
{
    /**
     * Execute the action to get all unique sizes grouped by size category.
     */
    public function execute(GetSizesRequest $request): array
    {
        $filters = $request->validatedWithDefaults();

        $rows = Product::selectRaw('size, size_category')
            ->when($filters['code'] ?? null, function ($q, $value) {
                return $q->where('code', 'like', "%{$value}%");
            })
            ->when($filters['main_category_id'] ?? null, fn ($q, $v) => $q->where('main_category_id', $v))
            ->when($filters['sub_category_id'] ?? null, fn ($q, $v) => $q->where('sub_category_id', $v))
            ->when($filters['brand_id'] ?? null, fn ($q, $v) => $q->where('brand_id', $v))
            // Only surface sizes from products in an online-visible category (matches /categories).
            ->whereHas('mainCategory', fn ($catQ) => $catQ->where('online_visibility_flag', true))
            ->whereNotNull('size')
            ->where('size', '!=', '')
            ->groupBy('size', 'size_category')
            ->get();

        $young = [];
        $adult = [];

        foreach ($rows as $row) {
            $size = (string) $row->size;

            // Fall back to on-the-fly classification for legacy rows that were
            // never backfilled (defensive; the migration backfills existing data).
            $category = $row->size_category ?: Product::classifySizeCategory($size);

            if ($category === Product::SIZE_CATEGORY_YOUNG) {
                $young[$size] = ['size' => $size];
            } else {
                $adult[$size] = ['size' => $size];
            }
        }

        $young = array_values($young);
        $adult = array_values($adult);

        $sortDesc = function (array &$items): void {
            usort($items, fn ($a, $b) => strnatcmp($b['size'], $a['size']));
        };
        $sortDesc($young);
        $sortDesc($adult);

        return [
            'young_sizes' => $young,
            'adult_sizes' => $adult,
            // Backwards-compatible aliases for older API consumers.
            'kids_sizes' => $young,
            'other_sizes' => $adult,
        ];
    }
}
