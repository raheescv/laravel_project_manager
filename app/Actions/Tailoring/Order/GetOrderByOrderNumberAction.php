<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrder;
use Exception;

class GetOrderByOrderNumberAction
{
    public function execute($orderNo)
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
                        'categoryModelType:id,name',
                        'product' => function ($q) {
                            $q->select('id', 'name')->withSum([
                                'inventories as stock_quantity' => fn ($q2) => $q2->where('branch_id', session('branch_id')),
                            ], 'quantity');
                        },
                        'unit:id,name',
                        'tailor:id,name',
                        'order',
                    ])->orderBy('item_no');
                },
                'payments' => function ($query) {
                    $query->with('paymentMethod:id,name')->orderBy('date');
                },
                'measurements',
            ])->where('order_no', $orderNo)->first();

            if (! $order) {
                throw new Exception('Order not found with order number: '.$orderNo);
            }

            // Merge measurements into items for frontend compatibility
            $order->appendMeasurementsToItems();

            $return['success'] = true;
            $return['message'] = 'Tailoring Order retrieved successfully';
            $return['data'] = $order;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
