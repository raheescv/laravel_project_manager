<?php

namespace App\Actions\Tailoring\Order\Item;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use App\Models\TailoringCategoryMeasurement;
use App\Models\TailoringOrder;
use App\Models\TailoringOrderItem;
use App\Models\TailoringOrderMeasurement;
use Exception;

class UpdateTailoringItemAction
{
    public function execute($id, array $data, int $user_id): array
    {
        try {
            $item = TailoringOrderItem::findOrFail($id);
            $data['updated_by'] = $user_id;
            $order = $item->order;
            $oldInventoryId = $item->inventory_id;
            $oldProductId = $item->product_id;
            $oldTotalUsed = (float) ($item->used_quantity ?? 0) + (float) ($item->wastage ?? 0);

            // save measurement
            $categoryId = $data['tailoring_category_id'] ?? $item->tailoring_category_id;
            if ($categoryId) {
                // Fetch only keys that are defined for this category
                $activeKeys = TailoringCategoryMeasurement::where('tailoring_category_id', $categoryId)
                    ->where('is_active', true)
                    ->pluck('field_key')
                    ->toArray();
                $measurementData = [
                    'tailoring_order_id' => $item->tailoring_order_id,
                    'tailoring_category_id' => $categoryId,
                ];
                $dynamicData = [];

                $measurementData['tailoring_category_model_id'] = $data['tailoring_category_model_id'];
                $measurementData['tailoring_category_model_type_id'] = $data['tailoring_category_model_type_id'];

                if (isset($data['tailoring_notes'])) {
                    $measurementData['tailoring_notes'] = $data['tailoring_notes'];
                }
                foreach ($activeKeys as $key) {
                    if (array_key_exists($key, $data)) {
                        $value = $data[$key];
                        if (in_array($key, ['tailoring_category_model_id', 'tailoring_category_model_type_id', 'tailoring_notes', 'id', 'tailoring_order_id', 'tailoring_category_id'])) {
                            continue;
                        }
                        $dynamicData[(string) $key] = $value;
                    }
                }
                $measurementData['data'] = (object) $dynamicData;
                TailoringOrderMeasurement::updateOrCreate(
                    [
                        'tailoring_order_id' => $item->tailoring_order_id,
                        'tailoring_category_id' => $categoryId,
                        'tailoring_category_model_id' => $data['tailoring_category_model_id'],
                        'tailoring_category_model_type_id' => $data['tailoring_category_model_type_id'] ?? null,
                    ],
                    $measurementData
                );
            }

            validationHelper(TailoringOrderItem::rules($id), $data);
            $item->fill($data);
            $item->save();

            // Update order totals
            $order = $order ?: $item->order;
            if ($order) {
                $order->calculateTotals();
                $order->save();
            }

            if (
                $order
                && $oldTotalUsed > 0
                && (($oldInventoryId && $oldInventoryId !== $item->inventory_id) || ($oldProductId && $oldProductId !== $item->product_id))
            ) {
                $this->adjustInventoryForProductChange($order, $item, $oldInventoryId, $oldTotalUsed, $user_id);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Tailoring Order Item';
            $return['data'] = $item;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    private function adjustInventoryForProductChange(TailoringOrder $order, TailoringOrderItem $item, ?int $oldInventoryId, float $quantity, int $user_id): void
    {
        if (! $order->branch_id) {
            throw new Exception('Order branch is required to update inventory.', 1);
        }

        if (! empty($oldInventoryId)) {
            $oldInventory = Inventory::withoutGlobalScopes()
                ->where('id', $oldInventoryId)
                ->where('branch_id', $order->branch_id)
                ->first();

            if (! $oldInventory) {
                throw new Exception('Old inventory not found for item #'.$item->item_no, 1);
            }

            $this->applyInventoryDelta($oldInventory, $quantity, $order, $item, $user_id, 'Tailoring order item product change (return)');
        }

        if (! empty($item->inventory_id)) {
            $newInventory = Inventory::withoutGlobalScopes()
                ->where('id', $item->inventory_id)
                ->where('branch_id', $order->branch_id)
                ->first();

            if (! $newInventory) {
                throw new Exception('New inventory not found for item #'.$item->item_no, 1);
            }

            $this->applyInventoryDelta($newInventory, -1 * $quantity, $order, $item, $user_id, 'Tailoring order item product change (consume)');
        }
    }

    private function applyInventoryDelta(Inventory $inventory, float $delta, TailoringOrder $order, TailoringOrderItem $item, int $user_id, string $remarksPrefix): void
    {
        if ($delta === 0.0) {
            return;
        }

        $payload = array_merge($inventory->toArray(), [
            'quantity' => (float) $inventory->quantity + $delta,
            'model' => 'TailoringOrderItem',
            'model_id' => $item->id,
            'remarks' => "{$remarksPrefix}: Order #{$order->order_no} (item #{$item->item_no})",
            'updated_by' => $user_id,
        ]);

        $response = (new UpdateAction())->execute($payload, $inventory->id);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }
}
