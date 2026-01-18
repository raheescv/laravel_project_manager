<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrder;
use Exception;

class SubmitOrderCompletionAction
{
    public function execute($orderId, $data, $userId)
    {
        try {
            $order = TailoringOrder::findOrFail($orderId);
            $order->updated_by = $userId;

            // Update completion status
            $order->completion_status = 'completed';
            if (isset($data['completion_date'])) {
                $order->completion_date = $data['completion_date'];
            } else {
                $order->completion_date = date('Y-m-d');
            }

            // Update selected items
            if (isset($data['selected_item_ids']) && is_array($data['selected_item_ids'])) {
                $order->items()->whereIn('id', $data['selected_item_ids'])->update([
                    'is_selected_for_completion' => true,
                    'item_completion_date' => $order->completion_date,
                ]);

                // Update item completion data if provided
                if (isset($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as $itemData) {
                        if (isset($itemData['id']) && in_array($itemData['id'], $data['selected_item_ids'])) {
                            $item = $order->items()->find($itemData['id']);
                            if ($item) {
                                $item->updateCompletion($itemData);
                            }
                        }
                    }
                }
            }

            // Update order status if all items completed
            $totalItems = $order->items()->count();
            $completedItems = $order->items()->where('is_selected_for_completion', true)->count();
            if ($totalItems > 0 && $totalItems == $completedItems) {
                $order->status = 'completed';
            }

            $order->save();

            // TODO: Update stock inventory if integrated
            // TODO: Record tailor commissions if needed

            $return['success'] = true;
            $return['message'] = 'Successfully Submitted Order Completion';
            $return['data'] = $order->fresh();
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
