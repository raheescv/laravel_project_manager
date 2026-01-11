<?php

namespace App\Actions\Product\Inventory\StockCheck\Item;

use App\Models\Inventory;
use App\Models\StockCheck;
use App\Models\StockCheckItem;
use Exception;

class ScanBarcodeAction
{
    public function execute(int $stockCheckId, string $barcode): array
    {
        try {
            $stockCheck = StockCheck::findOrFail($stockCheckId);

            // Find inventory by barcode
            $inventory = Inventory::withoutGlobalScopes()
                ->where('barcode', $barcode)
                ->where('branch_id', $stockCheck->branch_id)
                ->whereNull('employee_id')
                ->first();

            if (! $inventory) {
                throw new Exception('Barcode not found in branch inventory');
            }

            // Find matching StockCheckItem
            $item = StockCheckItem::where('stock_check_id', $stockCheckId)
                ->where('inventory_id', $inventory->id)
                ->where('product_id', $inventory->product_id)
                ->first();

            if (! $item) {
                throw new Exception('Item not found in stock check');
            }

            // Increment physical_quantity
            $item->increment('physical_quantity',1);
            $item->refresh();

            $return['success'] = true;
            $return['message'] = 'Barcode scanned successfully';
            $return['data'] = $item->toArray();
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = 'Failed to scan barcode: '.$e->getMessage();
            $return['data'] = [];
        }

        return $return;
    }
}
