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
