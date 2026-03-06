<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrder;
use App\Models\TailoringOrderItem;

class CompletionDataLoader
{
    /**
     * Shared relationships for tailoring order items in completion flows.
     */
    public static function itemRelations(): array
    {
        return [
            'category' => fn ($q) => $q->with('activeMeasurements'),
            'categoryModel',
            'categoryModelType',
            'inventory:id,product_id,branch_id,quantity,barcode,batch',
            'product:id,name',
            'unit',
            'tailorAssignments.tailor:id,name',
            'latestTailorAssignment.tailor:id,name',
        ];
    }

    /**
     * Reload a full order with completion-specific relations.
     */
    public function loadOrder(TailoringOrder $order): TailoringOrder
    {
        $order->refresh();
        $order->load([
            'items' => function ($query) {
                $query->with(self::itemRelations())->orderBy('item_no');
            },
            'measurements',
        ]);
        $order->appendMeasurementsToItems();

        return $order;
    }

    /**
     * Reload a single item with completion-specific relations and measurements.
     */
    public function loadItem(TailoringOrderItem $item): TailoringOrderItem
    {
        $item = $item->fresh(self::itemRelations());

        $order = $item->order()->with(['measurements.category.activeMeasurements'])->first();
        $order->setRelation('items', collect([$item]));
        $order->appendMeasurementsToItems();

        return $order->items->first();
    }
}
