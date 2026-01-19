<?php

namespace App\Actions\Tailoring\Order\Item;

use App\Models\TailoringOrderItem;
use Exception;

class UpdateTailoringItemAction
{
    public function execute($id, array $data, int $user_id): array
    {
        try {
            $item = TailoringOrderItem::findOrFail($id);
            $data['updated_by'] = $user_id;

            // save measurement
            $categoryId = $data['tailoring_category_id'] ?? $item->tailoring_category_id;
            if ($categoryId) {
                 \App\Models\TailoringOrderMeasurement::updateOrCreate(
                    [
                        'tailoring_order_id' => $item->tailoring_order_id,
                        'tailoring_category_id' => $categoryId,
                    ],
                    $data
                );
            }

            validationHelper(TailoringOrderItem::rules($id), $data);
            $item->fill($data);
            $item->calculateAmount();
            $item->save();

            // Update order totals
            $order = $item->order;
            $order->calculateTotals();
            $order->save();

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Tailoring Order Item';
            $return['data'] = $item;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
