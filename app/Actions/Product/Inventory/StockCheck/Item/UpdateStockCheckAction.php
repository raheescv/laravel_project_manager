<?php

namespace App\Actions\Product\Inventory\StockCheck\Item;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\StockCheckItem;
use Exception;
use Illuminate\Support\Facades\DB;

class UpdateStockCheckAction
{
    public function execute(int $stockCheckId, array $items, int $userId): array
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

                if ($item->status == 'completed') {
                    $sourceData = $item->inventory->toArray();
                    $sourceData['quantity'] = $item->physical_quantity;
                    $sourceData['model'] = 'StockCheckItem';
                    $sourceData['model_id'] = $item->id;
                    $sourceData['remarks'] = 'Stock Check Updation : '.$item->stockCheck->title;
                    $sourceData['updated_by'] = $userId;

                    $response = (new UpdateAction())->execute($sourceData, $item->inventory_id);
                    if (! $response['success']) {
                        throw new Exception('Failed to update source inventory: '.$response['message'], 1);
                    }
                }

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
