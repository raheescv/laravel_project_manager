<?php

namespace App\Actions\Sale\Pos;

use App\Models\Inventory;
use App\Models\SaleItem;
use App\Support\Sale\OutOfStockSales;
use Illuminate\Support\Facades\Log;

class GetProductsAction
{
    public function execute(array $filters = [], ?int $branchId = null)
    {
        try {
            $branchId = $branchId ?? session('branch_id');
            $saleType = $filters['sale_type'] ?? 'normal';

            $isFavorite = ($filters['category_id'] ?? null) === 'favorite';

            $query = $this->baseQuery($filters, $branchId)
                ->when($isFavorite, fn ($q) => $q->where('products.is_favorite', true));

            $total = (clone $query)->count();

            if ($isFavorite && $total === 0 && empty($filters['search'])) {
                $query = $this->topSellingQuery($filters, $branchId);
                $total = (clone $query)->count();
            }

            $products = $query->limit(50)
                ->get()
                ->map(fn ($inventory) => $this->formatProduct($inventory, $saleType));

            return ['success' => true, 'data' => $products, 'total' => $total];
        } catch (\Exception $e) {
            Log::error('Error loading products: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function baseQuery(array $filters, ?int $branchId)
    {
        return Inventory::with(['product', 'product.unit'])
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('categories', 'products.main_category_id', '=', 'categories.id')
            ->select('inventories.*')
            ->whereNull('inventories.employee_id')
            ->where('inventories.branch_id', $branchId)
            ->where('products.is_selling', true)
            ->where('categories.sale_visibility_flag', true)
            ->when(OutOfStockSales::hiddenFromSaleSelection(), fn ($q) => $q->where('inventories.quantity', '>', 0))
            ->when(! empty($filters['type']), fn ($q) => $q->where('products.type', $filters['type']))
            ->when(! empty($filters['category_id']) && $filters['category_id'] !== 'favorite', fn ($q) => $q->where('products.main_category_id', $filters['category_id']))
            ->when(! empty($filters['search']), function ($q) use ($filters): void {
                $search = trim($filters['search']);
                $q->where(fn ($s) => $s->where('products.name', 'LIKE', "%{$search}%")->orWhere('products.barcode', 'LIKE', "%{$search}%"));
            });
    }

    /**
     * Fallback for an empty Favorites list: the branch's ten best-selling items by completed-sale quantity.
     */
    private function topSellingQuery(array $filters, ?int $branchId)
    {
        $soldQuantities = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.branch_id', $branchId)
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id, SUM(sale_items.quantity) as sold_quantity');

        $topInventoryIds = $this->baseQuery($filters, $branchId)
            ->joinSub($soldQuantities, 'sold', 'sold.product_id', '=', 'products.id')
            ->orderByDesc('sold.sold_quantity')
            ->limit(10)
            ->pluck('inventories.id');

        return $this->baseQuery($filters, $branchId)
            ->whereIn('inventories.id', $topInventoryIds)
            ->joinSub($soldQuantities, 'sold', 'sold.product_id', '=', 'products.id')
            ->orderByDesc('sold.sold_quantity');
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
            'original_price' => (float) $product->mrp,
            'stock' => $inventory->quantity ?? 0,
            'category_id' => $product->main_category_id,
            'product_id' => $inventory->product_id,
            'branch_id' => $inventory->branch_id,
            'image' => $product->thumbnail ?? tenant_cache('logo'),
            'unit_id' => $product->unit_id,
            'unit_name' => $product->unit->name ?? '',
            'conversion_factor' => 1,
            'units' => $product->getResolvedUnits(),
        ];
    }
}
