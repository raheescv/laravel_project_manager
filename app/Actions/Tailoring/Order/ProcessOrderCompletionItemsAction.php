<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrder;
use Exception;

class ProcessOrderCompletionItemsAction
{
    /**
     * Update order items from payload and apply stock updates for items with product_id.
     *
     * @param  array<int, array<string, mixed>>  $itemsData  Each element must have 'id'; may have used_quantity, wastage, etc.
     * @param  array<int>|null  $selectedItemIds  If set, only process items whose id is in this array
     * @throws Exception
     */
    public function execute(TailoringOrder $order, array $itemsData, int $userId, ?array $selectedItemIds = null): void
    {
        $itemsForStockUpdate = [];

        foreach ($itemsData as $itemData) {
            if (! isset($itemData['id'])) {
                continue;
            }
            if ($selectedItemIds !== null && ! in_array($itemData['id'], $selectedItemIds)) {
                continue;
            }

            $item = $order->items()->find($itemData['id']);
            if (! $item) {
                continue;
            }

            $oldQuantity = (float) ($item->used_quantity ?? 0) + (float) ($item->wastage ?? 0);
            $newQuantity = (float) ($itemData['used_quantity'] ?? 0) + (float) ($itemData['wastage'] ?? 0);

            $item->updateCompletion($itemData);

            if ($item->product_id) {
                $item->refresh();
                $itemsForStockUpdate[] = [
                    'item' => $item,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                ];
            }
        }

        if (! empty($itemsForStockUpdate)) {
            $stockResponse = (new StockUpdateAction())->execute($order, $itemsForStockUpdate, $userId);
            if (! $stockResponse['success']) {
                throw new Exception($stockResponse['message'], 1);
            }
        }
    }
}
