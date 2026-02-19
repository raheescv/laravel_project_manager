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
                                if (! isset($itemData['tailor_assignments']) || ! is_array($itemData['tailor_assignments']) || empty($itemData['tailor_assignments'])) {
                                    $itemData['tailor_assignments'] = [];
                                    if (isset($itemData['tailor_assignment']) && is_array($itemData['tailor_assignment'])) {
                                        $itemData['tailor_assignments'][] = $itemData['tailor_assignment'];
                                    }
                                }

                                $units = max(1, (int) round((float) $item->quantity));
                                if (empty($itemData['tailor_assignments'])) {
                                    $itemData['tailor_assignments'][] = [];
                                }
                                if (count($itemData['tailor_assignments']) < $units) {
                                    $itemData['tailor_assignments'] = array_merge(
                                        $itemData['tailor_assignments'],
                                        array_fill(0, $units - count($itemData['tailor_assignments']), [])
                                    );
                                }
                                $itemData['tailor_assignments'] = array_slice($itemData['tailor_assignments'], 0, $units);
                                $itemData['tailor_assignment'] = $itemData['tailor_assignments'][0];
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
                        'inventory:id,product_id,branch_id,quantity,barcode,batch',
                        'product:id,name',
                        'unit',
                        'tailorAssignments.tailor:id,name',
                        'latestTailorAssignment.tailor:id,name',
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
