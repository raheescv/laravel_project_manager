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
                        'product:id,name,barcode',
                        'unit:id,name',
                        'tailor:id,name',
                        'order',
                        'audits.user',
                    ])->orderBy('item_no');
                },
                'payments' => function ($query) {
                    $query->with(['paymentMethod:id,name', 'audits.user'])->orderBy('date');
                },
                'measurements.audits.user',
                'journals.entries.account',
                'audits.user',
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
