<?php

namespace App\Actions\Sale\Pos;

use App\Models\Inventory;
use Illuminate\Support\Facades\Log;

class GetProductsAction
{
    public function execute(array $filters = [], ?int $branchId = null)
    {
        try {
            $branchId = $branchId ?? session('branch_id');
            $saleType = $filters['sale_type'] ?? 'normal';

            $products = Inventory::with(['product'])
                ->whereNull('inventories.employee_id')
                ->where('inventories.branch_id', $branchId)
                ->whereHas('product', function ($q) use ($filters): void {
                    $q->where('is_selling', true)
                        ->whereHas('mainCategory', fn ($c) => $c->where('sale_visibility_flag', true));

                    if (! empty($filters['type'])) {
                        $q->where('type', $filters['type']);
                    }

                    if (! empty($filters['category_id']) && $filters['category_id'] !== 'favorite') {
                        $q->where('main_category_id', $filters['category_id']);
                    } elseif (isset($filters['category_id']) && $filters['category_id'] === 'favorite') {
                        $q->where('is_favorite', true);
                    }

                    if (! empty($filters['search'])) {
                        $search = trim($filters['search']);
                        $q->where(fn ($s) => $s->where('name', 'LIKE', "%{$search}%")->orWhere('barcode', 'LIKE', "%{$search}%"));
                    }
                })
                ->limit(50)
                ->get()
                ->map(fn ($inventory) => $this->formatProduct($inventory, $saleType));

            return ['success' => true, 'data' => $products];
        } catch (\Exception $e) {
            Log::error('Error loading products: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function formatProduct($inventory, string $saleType): array
    {
        $product = $inventory->product;

        return [
            'id' => $inventory->id,
            'name' => $product->name,
            'type' => $product->type,
            'barcode' => $product->barcode,
            'size' => $product->size,
            'code' => $product->code,
            'mrp' => $product->saleTypePrice($saleType),
            'stock' => $inventory->quantity ?? 0,
            'category_id' => $product->main_category_id,
            'product_id' => $inventory->product_id,
            'branch_id' => $inventory->branch_id,
            'image' => $product->thumbnail ?? cache('logo'),
            'unit_id' => $product->unit_id,
            'unit_name' => $product->unit->name ?? '',
            'conversion_factor' => 1,
            'units' => $product->getResolvedUnits(),
        ];
    }
}
