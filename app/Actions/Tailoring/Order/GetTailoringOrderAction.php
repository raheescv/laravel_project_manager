<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrder;

class GetTailoringOrderAction
{
    public function execute($id)
    {
        try {
            $order = TailoringOrder::with([
                'branch:id,name',
                'account:id,name,mobile',
                'salesman:id,name',
                'rack:id,name',
                'cutter:id,name',
                'items' => function ($query) {
                    $query->with([
                        'category' => function ($q) {
                            $q->with('activeMeasurements');
                        },
                        'categoryModel:id,name',
                        'product:id,name',
                        'unit:id,name',
                        'tailor:id,name',
                        'order',
                    ])->orderBy('item_no');
                },
                'payments' => function ($query) {
                    $query->with('paymentMethod:id,name')->orderBy('date');
                },
                'measurements',
                'journals.entries.account',
            ])->findOrFail($id);

            // Merge measurements into items for frontend compatibility
            $order->appendMeasurementsToItems();
            $return['success'] = true;
            $return['message'] = 'Tailoring Order retrieved successfully.';
            $return['data'] = $order;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
