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

        $branchId = $filters['branch_id'] ?? null;

        // The showcase renders a stock count under every size chip and greys out
        // the ones with nothing on the shelf. Counting per size in SQL keeps that
        // to one request — the alternative, a lookup per size, is a request storm
        // on a screen that shows twenty of them.
        //
        // The inventories join is keyed on a tenant-scoped product id, so it
        // cannot pull another tenant's stock even though the join itself is
        // outside the global scope.
        $rows = Product::query()
            ->selectRaw('products.size as size, products.size_category as size_category')
            ->selectRaw('COUNT(DISTINCT products.id) as product_count')
            ->selectRaw('COALESCE(SUM(inventories.quantity), 0) as stock_total')
            ->leftJoin('inventories', function ($join) use ($branchId) {
                $join->on('inventories.product_id', '=', 'products.id');
                if ($branchId) {
                    $join->where('inventories.branch_id', '=', $branchId);
                }
            })
            ->when($filters['code'] ?? null, function ($q, $value) {
                return $q->where('products.code', 'like', "%{$value}%");
            })
            ->when($filters['main_category_id'] ?? null, fn ($q, $v) => $q->where('products.main_category_id', $v))
            ->when($filters['sub_category_id'] ?? null, fn ($q, $v) => $q->where('products.sub_category_id', $v))
            ->when($filters['brand_id'] ?? null, fn ($q, $v) => $q->where('products.brand_id', $v))
            // Only surface sizes from products in an online-visible category (matches /categories).
            ->whereHas('mainCategory', fn ($catQ) => $catQ->where('online_visibility_flag', true))
            ->whereNotNull('products.size')
            ->where('products.size', '!=', '')
            ->groupBy('products.size', 'products.size_category')
            ->get();

        $young = [];
        $adult = [];

        foreach ($rows as $row) {
            $size = (string) $row->size;

            // Fall back to on-the-fly classification for legacy rows that were
            // never backfilled (defensive; the migration backfills existing data).
            $category = $row->size_category ?: Product::classifySizeCategory($size);

            $entry = [
                'size' => $size,
                'product_count' => (int) $row->product_count,
                'stock_total' => (int) $row->stock_total,
                'in_stock' => ((int) $row->stock_total) > 0,
            ];

            if ($category === Product::SIZE_CATEGORY_YOUNG) {
                $young[$size] = $entry;
            } else {
                $adult[$size] = $entry;
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
