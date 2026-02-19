<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrder;

class SubmitOrderCompletionAction
{
    public function execute($orderId, $data, $userId)
    {
        try {
            $order = TailoringOrder::findOrFail($orderId);
            $order->updated_by = $userId;

            // Update completion status
            if (isset($data['completion_date'])) {
                $order->completion_date = $data['completion_date'];
            } else {
                $order->completion_date = date('Y-m-d');
            }
            // Update status of selected items
            if (isset($data['selected_item_ids']) && is_array($data['selected_item_ids'])) {
                $order->items()->whereIn('id', $data['selected_item_ids'])->update(['is_selected_for_completion' => true]);

                // Prepare item data and update items + stock via shared action
                if (isset($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as &$itemData) {
                        if (isset($itemData['id']) && in_array($itemData['id'], $data['selected_item_ids'])) {
                            $item = $order->items()->find($itemData['id']);
                            if ($item) {
                                if (empty($itemData['item_completion_date']) && empty($item->item_completion_date)) {
                                    $itemData['item_completion_date'] = $order->completion_date;
                                }
                                $itemData['completed_quantity'] = $item->quantity;
                            }
                        }
                    }
                    unset($itemData);
                    (new ProcessOrderCompletionItemsAction())->execute($order, $data['items'], (int) $userId, $data['selected_item_ids']);
                }
            }

            // Update order status if all items completed
            $totalItems = $order->items()->count();
            $completedItems = $order->items()->where('is_selected_for_completion', true)->count();

            if ($totalItems > 0 && $totalItems == $completedItems) {
                $order->status = 'completed';
            }

            $order->save();

            $order = $order->fresh([
                'items' => function ($query) {
                    $query->with([
                        'category' => fn ($q) => $q->with('activeMeasurements'),
                        'categoryModel',
                        'categoryModelType',
                        'product' => fn ($q) => $q->select('id', 'name')->withSum([
                            'inventories as stock_quantity' => fn ($q2) => $q2->where('branch_id', session('branch_id')),
                        ], 'quantity'),
                        'unit',
                        'tailor',
                    ])->orderBy('item_no');
                },
                'measurements',
            ]);
            $order->appendMeasurementsToItems();

            $return['success'] = true;
            $return['message'] = 'Successfully Submitted Order Completion';
            $return['data'] = $order;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
