<?php

namespace App\Actions\Product\Inventory\StockCheck\Item;

use App\Models\StockCheckItem;
use Exception;
use Illuminate\Support\Facades\DB;

class UpdateStockCheckAction
{
    public function execute(int $stockCheckId, array $items): array
    {
        try {
            DB::beginTransaction();

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

                $updatedItems[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'inventory_id' => $item->inventory_id,
                    'physical_quantity' => $item->physical_quantity,
                    'recorded_quantity' => $item->recorded_quantity,
                    'status' => $item->status,
                ];
            }

            DB::commit();

            $return['success'] = true;
            $return['message'] = 'Stock check updated successfully';
            $return['data'] = $updatedItems;
        } catch (Exception $e) {
            DB::rollBack();

            $return['success'] = false;
            $return['message'] = 'Failed to update stock check: '.$e->getMessage();
            $return['data'] = [];
        }

        return $return;
    }
}
