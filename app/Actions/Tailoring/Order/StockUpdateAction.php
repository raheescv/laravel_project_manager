<?php

namespace App\Actions\Tailoring\Order;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use App\Models\TailoringOrder;
use App\Models\TailoringOrderItem;
use Exception;

class StockUpdateAction
{
    public const REMARKS_COMPLETION = 'Tailoring order completion';

    public const REMARKS_REVERSAL = 'Tailoring order reversal (order deleted)';

    /**
     * Update inventory for items whose used_quantity or wastage changed.
     * Total consumption = used_quantity + wastage; inventory is reduced by the delta of that total.
     *
     * @param  array<int, array{item: TailoringOrderItem, old_quantity: float, new_quantity: float}>  $itemsWithQuantity
     */
    public function execute(TailoringOrder $order, array $itemsWithQuantity, int $userId, string $remarksPrefix = self::REMARKS_COMPLETION): array
    {
        try {
            if (! $order->branch_id) {
                throw new Exception('Order branch is required to update inventory.', 1);
            }

            foreach ($itemsWithQuantity as $entry) {
                $this->updateItemStock(
                    $entry['item'],
                    (float) ($entry['old_quantity'] ?? 0),
                    (float) ($entry['new_quantity'] ?? 0),
                    $order,
                    $order->branch_id,
                    $userId,
                    $remarksPrefix
                );
            }

            return $this->result(true, 'Successfully Updated Inventory');
        } catch (Exception $e) {
            return $this->result(false, $e->getMessage());
        }
    }

    /**
     * Reverse stock for a full order: add back (used_quantity + wastage) for each item.
     */
    public function reverseStockForOrder(TailoringOrder $order, int $userId): array
    {
        $order->loadMissing('items');

        $itemsWithQuantity = $order->items
            ->filter(fn (TailoringOrderItem $item) => $item->product_id)
            ->map(fn (TailoringOrderItem $item) => [
                'item' => $item,
                'old_quantity' => $item->total_quantity_used,
                'new_quantity' => 0.0,
            ])
            ->filter(fn (array $row) => $row['old_quantity'] > 0)
            ->values()
            ->all();

        if (empty($itemsWithQuantity)) {
            return $this->result(true, 'No stock to reverse');
        }

        return $this->execute($order, $itemsWithQuantity, $userId, self::REMARKS_REVERSAL);
    }

    private function result(bool $success, string $message, array $data = []): array
    {
        return ['success' => $success, 'message' => $message, 'data' => $data];
    }

    private function updateItemStock(TailoringOrderItem $item, float $oldQty, float $newQty, TailoringOrder $order, int $branchId, int $userId, string $remarksPrefix): void
    {
        if (! $item->product_id) {
            return;
        }

        $delta = $newQty - $oldQty;
        if ($delta === 0.0) {
            return;
        }

        $inventory = Inventory::withoutGlobalScopes()
            ->where('product_id', $item->product_id)
            ->where('branch_id', $branchId)
            ->first();

        if (! $inventory) {
            throw new Exception('Inventory not found for product ID: '.$item->product_id, 1);
        }

        $payload = array_merge($inventory->toArray(), [
            'quantity' => (float) $inventory->quantity - $delta,
            'model' => 'TailoringOrderItem',
            'model_id' => $item->id,
            'remarks' => "{$remarksPrefix}: Order #{$order->order_no} (item #{$item->item_no})",
            'updated_by' => $userId,
        ]);

        $response = (new UpdateAction())->execute($payload, $inventory->id);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }
}
