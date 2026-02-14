<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrder;
use Exception;

class UpdateOrderCompletionAction
{
    public function execute($orderId, $data, $userId)
    {
        try {
            $order = TailoringOrder::findOrFail($orderId);
            $order->updated_by = $userId;

            // Update order completion fields
            if (isset($data['rack_id'])) {
                $order->rack_id = $data['rack_id'];
            }
            if (isset($data['cutter_id'])) {
                $order->cutter_id = $data['cutter_id'];
            }
            if (isset($data['completion_date'])) {
                $order->completion_date = $data['completion_date'];
            }
            if (isset($data['completion_status'])) {
                $order->completion_status = $data['completion_status'];
            }

            $order->save();

            $itemsForStockUpdate = [];

            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['id'])) {
                        $item = $order->items()->find($itemData['id']);
                        if ($item) {
                            $oldQuantity = $item->used_quantity + $item->wastage;
                            $newQuantity = $itemData['used_quantity'] + $itemData['wastage'];

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
                    }
                }

                if (! empty($itemsForStockUpdate)) {
                    $stockResponse = (new StockUpdateAction())->execute($order, $itemsForStockUpdate, (int) $userId);
                    if (! $stockResponse['success']) {
                        throw new Exception($stockResponse['message'], 1);
                    }
                }
            }

            $order->refresh();
            $order->load(['items.category', 'items.categoryModel', 'items.product', 'items.unit', 'items.tailor', 'measurements']);
            $order->appendMeasurementsToItems();

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Order Completion';
            $return['data'] = $order;
        } catch (Exception $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
