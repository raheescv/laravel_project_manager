<?php

namespace App\Actions\Product;

use App\Models\Inventory;
use App\Models\InventoryTransferItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class ProductReportAction
{
    public function execute($params = [])
    {

        $query = Product::query()
            ->leftJoin('categories', 'products.main_category_id', '=', 'categories.id');

        // Get current inventory
        $inventoryQuery = Inventory::select('product_id', DB::raw('SUM(quantity) as current_stock'))
            ->when($params['branch_id'] ?? '', function ($q, $value) {
                return $q->where('branch_id', $value);
            })
            ->groupBy('product_id');

        // Get sales count
        $salesQuery = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->when($params['from_date'] ?? '', function ($q, $value) {
                return $q->whereDate('sales.date', '>=', $value);
            })
            ->when($params['to_date'] ?? '', function ($q, $value) {
                return $q->whereDate('sales.date', '<=', $value);
            })
            ->when($params['branch_id'] ?? '', function ($q, $value) {
                return $q->where('sales.branch_id', $value);
            })
            ->groupBy('product_id');

        // Get purchase count
        $purchaseQuery = PurchaseItem::select('product_id', DB::raw('SUM(quantity) as total_purchased'))
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.status', 'completed')
            ->when($params['from_date'] ?? '', function ($q, $value) {
                return $q->whereDate('purchases.date', '>=', $value);
            })
            ->when($params['to_date'] ?? '', function ($q, $value) {
                return $q->whereDate('purchases.date', '<=', $value);
            })
            ->when($params['branch_id'] ?? '', function ($q, $value) {
                return $q->where('purchases.branch_id', $value);
            })
            ->groupBy('product_id');

        $inventoryTransferItemQuery = InventoryTransferItem::query()
            ->join('inventory_transfers', 'inventory_transfers.id', '=', 'inventory_transfer_items.inventory_transfer_id')
            ->where('inventory_transfers.status', 'completed')
            ->when($params['from_date'] ?? '', function ($q, $value) {
                return $q->whereDate('inventory_transfers.date', '>=', $value);
            })
            ->when($params['to_date'] ?? '', function ($q, $value) {
                return $q->whereDate('inventory_transfers.date', '<=', $value);
            });
        $transferInQuery = clone $inventoryTransferItemQuery;
        // Get inventory transfer count
        $transferInQuery = $transferInQuery
            ->select('product_id', DB::raw('SUM(quantity) as transfer_in'))
            ->when($params['branch_id'] ?? '', function ($q, $value) {
                return $q->where('inventory_transfers.to_branch_id', $value);
            })
            ->groupBy('product_id');

        $transferOutQuery = clone $inventoryTransferItemQuery;
        $transferOutQuery = $transferOutQuery
            ->select('product_id', DB::raw('SUM(quantity) as transfer_out'))
            ->when($params['branch_id'] ?? '', function ($q, $value) {
                return $q->where('inventory_transfers.from_branch_id', $value);
            })
            ->groupBy('product_id');

        return $query->select(
            'products.id',
            'products.name',
            'products.code',
            'products.barcode',
            'categories.name as category_name',
            'inventory.current_stock',
            'sales.total_sold',
            'purchases.total_purchased',
            'transfer_in.transfer_in',
            'transfer_out.transfer_out'
        )
            ->when($params['search'] ?? '', function ($q, $value) {
                return $q
                    ->where('products.name', 'like', '%'.trim($value).'%')
                    ->orWhere('products.code', 'like', '%'.trim($value).'%');
            })
            ->when($params['barcode'] ?? '', function ($q, $value) {
                return $q->where('products.barcode', 'like', '%'.trim($value).'%');
            })
            ->when($params['main_category_id'] ?? '', function ($q, $value) {
                return $q->where('products.main_category_id', $value);
            })
            ->product()
            ->leftJoinSub($inventoryQuery, 'inventory', function ($join): void {
                $join->on('products.id', '=', 'inventory.product_id');
            })
            ->leftJoinSub($salesQuery, 'sales', function ($join): void {
                $join->on('products.id', '=', 'sales.product_id');
            })
            ->leftJoinSub($purchaseQuery, 'purchases', function ($join): void {
                $join->on('products.id', '=', 'purchases.product_id');
            })
            ->leftJoinSub($transferInQuery, 'transfer_in', function ($join): void {
                $join->on('products.id', '=', 'transfer_in.product_id');
            })
            ->leftJoinSub($transferOutQuery, 'transfer_out', function ($join): void {
                $join->on('products.id', '=', 'transfer_out.product_id');
            })
            ->orderBy($params['sortField'] ?? 'products.name', $params['sortDirection'] ?? 'asc');
    }
}
