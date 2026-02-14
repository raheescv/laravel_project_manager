<?php

namespace App\Actions\Tailoring\Order;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use App\Models\TailoringOrder;
use App\Models\TailoringOrderItem;
use Exception;

class StockUpdateAction
{
    /**
     * Update inventory for items whose used_quantity or wastage changed.
     * Total consumption = used_quantity + wastage; inventory is reduced by the delta of that total.
     *
     * @param  array<int, array{item: TailoringOrderItem, old_used_quantity: float, new_used_quantity: float, old_wastage: float, new_wastage: float}>  $itemsWithQuantity
     */
    public function execute(TailoringOrder $order, array $itemsWithQuantity, int $userId): array
    {
        try {
            $branchId = $order->branch_id;
            if (! $branchId) {
                throw new Exception('Order branch is required to update inventory.', 1);
            }

            foreach ($itemsWithQuantity as $entry) {
                $this->singleItem($entry['item'], (float) ($entry['old_quantity'] ?? 0), (float) ($entry['new_quantity'] ?? 0), $order, $branchId, $userId);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Inventory';
            $return['data'] = [];
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    private function singleItem(TailoringOrderItem $item, float $oldQuantity, float $newQuantity, TailoringOrder $order, int $branchId, int $userId): void
    {
        if (! $item->product_id) {
            return;
        }

        $delta = $newQuantity - $oldQuantity;
        if ($delta == 0) {
            return;
        }

        $inventory = Inventory::withoutGlobalScopes()
            ->where('product_id', $item->product_id)
            ->where('branch_id', $branchId)
            ->first();

        if (! $inventory) {
            throw new Exception('Inventory not found for product ID: '.$item->product_id, 1);
        }

        // Consumed more: reduce stock. Consumed less (reversal): increase stock.
        $inventoryData = $inventory->toArray();
        $inventoryData['quantity'] = (float) $inventory->quantity - $delta;
        $inventoryData['model'] = 'TailoringOrderItem';
        $inventoryData['model_id'] = $item->id;
        $inventoryData['remarks'] = 'Tailoring order completion: Order #'.$order->order_no.' (item #'.$item->item_no.')';
        $inventoryData['updated_by'] = $userId;

        $response = (new UpdateAction())->execute($inventoryData, $inventory->id);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }
}
