<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrder;

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
                        'category:id,name',
                        'categoryModel:id,name',
                        'product' => function ($q) {
                            $q->select('id', 'name')->withSum('inventories as stock_quantity', 'quantity');
                        },
                        'unit:id,name',
                        'tailor:id,name',
                    ])->orderBy('item_no');
                },
                'payments' => function ($query) {
                    $query->with('paymentMethod:id,name')->orderBy('date');
                },
                'measurements',
            ])->where('order_no', $orderNo)->first();

            if (! $order) {
                throw new \Exception('Order not found with order number: '.$orderNo);
            }

            // Merge measurements into items for frontend compatibility
            $order->appendMeasurementsToItems();

            $return['success'] = true;
            $return['message'] = 'Tailoring Order retrieved successfully';
            $return['data'] = $order;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
