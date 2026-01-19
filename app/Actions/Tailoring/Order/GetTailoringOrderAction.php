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
                        'category:id,name',
                        'categoryModel:id,name',
                        'product:id,name',
                        'unit:id,name',
                        'tailor:id,name',
                    ])->orderBy('item_no');
                },
                'payments' => function ($query) {
                    $query->with('paymentMethod:id,name')->orderBy('date');
                },
                'measurements'
            ])->findOrFail($id);

            // Merge measurements into items for frontend compatibility
            $measurements = $order->measurements->keyBy('tailoring_category_id');
            foreach ($order->items as $item) {
                if ($item->tailoring_category_id && isset($measurements[$item->tailoring_category_id])) {
                    $meas = $measurements[$item->tailoring_category_id];
                    $measurementAttributes = $meas->only([
                        'length', 'shoulder', 'sleeve', 'chest', 'stomach', 'sl_chest',
                        'sl_so', 'neck', 'bottom', 'mar_size', 'mar_model', 'cuff',
                        'cuff_size', 'cuff_cloth', 'cuff_model', 'neck_d_button',
                        'side_pt_size', 'collar', 'collar_size', 'collar_cloth',
                        'collar_model', 'regal_size', 'knee_loose', 'fp_down',
                        'fp_model', 'fp_size', 'pen', 'side_pt_model', 'stitching',
                        'button', 'button_no', 'mobile_pocket'
                    ]);
                    
                    foreach ($measurementAttributes as $key => $value) {
                        $item->$key = $value;
                    }
                }
            }

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
