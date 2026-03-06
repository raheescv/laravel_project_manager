<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrder;
use Exception;

class SaveOrderCompletionAction
{
    /**
     * Save order completion data.
     *
     * @param  int  $orderId
     * @param  array  $data
     * @param  int  $userId
     * @param  array  $options  Supported keys:
     *                          - default_completion_date (bool) : if true and completion_date not provided, set to today
     * @return array
     */
    public function execute($orderId, $data, $userId, $options = [])
    {
        try {
            $order = TailoringOrder::findOrFail($orderId);
            $order->updated_by = $userId;

            if (isset($data['rack_id'])) {
                $order->rack_id = $data['rack_id'];
            }
            if (isset($data['cutter_id'])) {
                $order->cutter_id = $data['cutter_id'];
            }
            if (array_key_exists('cutter_rating', $data)) {
                $order->cutter_rating = $data['cutter_rating'];
            }

            // completion date: respect provided value; otherwise optionally set to today for submit flows
            if (isset($data['completion_date'])) {
                $order->completion_date = $data['completion_date'];
            } elseif (! empty($options['default_completion_date'])) {
                $order->completion_date = date('Y-m-d');
            }

            $order->save();

            if (isset($data['items']) && is_array($data['items'])) {
                (new ProcessOrderCompletionItemsAction())->execute($order, $data['items'], (int) $userId);
            }

            $order = (new CompletionDataLoader())->loadOrder($order);

            $return['success'] = true;
            $return['message'] = ! empty($options['default_completion_date']) ? 'Successfully Submitted Order Completion' : 'Successfully Updated Order Completion';
            $return['data'] = $order;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
