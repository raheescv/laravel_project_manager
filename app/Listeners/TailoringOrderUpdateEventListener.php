<?php

namespace App\Listeners;

use App\Events\TailoringOrderUpdatedEvent;
use App\Models\TailoringOrderItem;

class TailoringOrderUpdateEventListener
{
    public function handle(TailoringOrderUpdatedEvent $event): void
    {
        $model = $event->model;
        switch ($event->action) {
            case 'item_quantity_change':
                $stats = TailoringOrderItem::query()
                    ->where('tailoring_order_id', $model->id)
                    ->selectRaw('SUM(quantity) as total_quantity')
                    ->selectRaw('SUM(completed_quantity) as completed_quantity')
                    ->selectRaw('SUM(delivered_quantity) as delivered_quantity')
                    ->first();

                $totalQuantity = (float) ($stats->total_quantity ?? 0);
                $completedQuantity = (float) ($stats->completed_quantity ?? 0);
                $deliveredQuantity = (float) ($stats->delivered_quantity ?? 0);

                $deliveryStatus = 'not delivered';
                if ($totalQuantity > 0 && $deliveredQuantity >= $totalQuantity) {
                    $deliveryStatus = 'delivered';
                } elseif ($deliveredQuantity > 0) {
                    $deliveryStatus = 'partially delivered';
                }

                $orderStatus = ($totalQuantity > 0 && $completedQuantity >= $totalQuantity) ? 'completed' : 'pending';

                $updates = [];
                if ($model->delivery_status !== $deliveryStatus) {
                    $updates['delivery_status'] = $deliveryStatus;
                }
                if ($model->status !== $orderStatus) {
                    $updates['status'] = $orderStatus;
                }
                if (! empty($updates)) {
                    $model->update($updates);
                }
                break;
        }
    }
}
