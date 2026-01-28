<?php

namespace App\Actions\Tailoring\Order\Item;

use App\Models\TailoringOrderItem;
use App\Models\TailoringOrderMeasurement;
use Exception;

class AddTailoringItemAction
{
    public function execute(array $data, int $user_id): array
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;

            // save measurement
            if (! empty($data['tailoring_category_id'])) {
                $measurement = TailoringOrderMeasurement::updateOrCreate(
                    [
                        'tailoring_order_id' => $data['tailoring_order_id'],
                        'tailoring_category_id' => $data['tailoring_category_id'],
                    ],
                    $data
                );
            }

            validationHelper(TailoringOrderItem::rules(), $data);

            $item = new TailoringOrderItem($data);
            $item->calculateAmount();
            $item->save();

            // Update order totals
            $order = $item->order;
            $order->calculateTotals();
            $order->save();

            $return['success'] = true;
            $return['message'] = 'Successfully Added Tailoring Order Item';
            $return['data'] = $item;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
