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

            $products = Inventory::with(['product', 'product.unit'])
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->join('categories', 'products.main_category_id', '=', 'categories.id')
                ->select('inventories.*')
                ->whereNull('inventories.employee_id')
                ->where('inventories.branch_id', $branchId)
                ->where('products.is_selling', true)
                ->where('categories.sale_visibility_flag', true)
                ->when(! empty($filters['type']), fn ($q) => $q->where('products.type', $filters['type']))
                ->when(! empty($filters['category_id']) && $filters['category_id'] !== 'favorite', fn ($q) => $q->where('products.main_category_id', $filters['category_id']))
                ->when(isset($filters['category_id']) && $filters['category_id'] === 'favorite', fn ($q) => $q->where('products.is_favorite', true))
                ->when(! empty($filters['search']), function ($q) use ($filters): void {
                    $search = trim($filters['search']);
                    $q->where(fn ($s) => $s->where('products.name', 'LIKE', "%{$search}%")->orWhere('products.barcode', 'LIKE', "%{$search}%"));
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
