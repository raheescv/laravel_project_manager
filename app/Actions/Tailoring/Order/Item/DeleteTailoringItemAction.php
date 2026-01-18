<?php

namespace App\Actions\Tailoring\Order\Item;

use App\Models\TailoringOrderItem;
use Exception;

class DeleteTailoringItemAction
{
    public function execute($id, int $user_id): array
    {
        try {
            $item = TailoringOrderItem::findOrFail($id);
            $order = $item->order;
            
            $item->deleted_by = $user_id;
            $item->save();
            $item->delete();

            // Update order totals
            $order->refresh();
            $order->calculateTotals();
            $order->save();

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Tailoring Order Item';
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
