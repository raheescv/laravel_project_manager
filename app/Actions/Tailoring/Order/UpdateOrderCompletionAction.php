<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrder;
use Exception;
use App\Actions\Tailoring\Order\ProcessOrderCompletionItemsAction;

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

            $order->save();

            if (isset($data['items']) && is_array($data['items'])) {
                (new ProcessOrderCompletionItemsAction())->execute($order, $data['items'], (int) $userId);
            }

            $order->refresh();
            $order->load([
                'items' => function ($query) {
                    $query->with([
                        'category' => fn ($q) => $q->with('activeMeasurements'),
                        'categoryModel',
                        'categoryModelType',
                        'product' => fn ($q) => $q->select('id', 'name')->withSum('inventories as stock_quantity', 'quantity'),
                        'unit',
                        'tailor',
                    ])->orderBy('item_no');
                },
                'measurements',
            ]);
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
