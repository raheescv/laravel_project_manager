<?php

namespace App\Actions\Product\Inventory\StockCheck\Item;

use App\Models\StockCheck;
use App\Models\StockCheckItem;
use Exception;
use Illuminate\Support\Facades\DB;

class UpdateStockCheckAction
{
    public function execute(int $stockCheckId, array $items): array
    {
        try {
            DB::beginTransaction();

            $stockCheck = StockCheck::findOrFail($stockCheckId);

            $updatedItems = [];

            foreach ($items as $itemData) {
                $item = StockCheckItem::where('id', $itemData['id'])
                    ->where('stock_check_id', $stockCheckId)
                    ->firstOrFail();

                $item->physical_quantity = $itemData['physical_quantity'];

                // Update status if provided
                if (isset($itemData['status']) && in_array($itemData['status'], ['pending', 'completed'])) {
                    $item->status = $itemData['status'];
                }

                $item->save();

                // Calculate difference
                $difference = $item->physical_quantity - $item->recorded_quantity;

                $updatedItems[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'physical_quantity' => $item->physical_quantity,
                    'recorded_quantity' => $item->recorded_quantity,
                    'difference' => $difference,
                    'status' => $item->status,
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Stock check updated successfully',
                'data' => $updatedItems,
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Failed to update stock check: '.$e->getMessage(),
                'data' => [],
            ];
        }
    }
}
