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
            
            $categoryId = $item->tailoring_category_id;
            $orderId = $item->tailoring_order_id;

            $item->deleted_by = $user_id;
            $item->save();
            $item->delete();

            // Check if any items remain for this category
            $remainingItemsCount = TailoringOrderItem::where('tailoring_order_id', $orderId)
                ->where('tailoring_category_id', $categoryId)
                ->count();

            if ($remainingItemsCount === 0) {
                 \App\Models\TailoringOrderMeasurement::where('tailoring_order_id', $orderId)
                    ->where('tailoring_category_id', $categoryId)
                    ->delete();
            }

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
