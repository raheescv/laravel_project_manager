<?php

namespace App\Actions\Product\ProductPrice;

use App\Models\ProductPrice;

class CreateAction
{
    public function execute($data)
    {
        try {
            if ($data['price_type'] != 'offer') {
                unset($data['start_date']);
                unset($data['end_date']);
            } else {
                if (! $data['start_date']) {
                    throw new \Exception('start date required', 1);
                }
                if (! $data['end_date']) {
                    throw new \Exception('end date required', 1);
                }
            }
            validationHelper(ProductPrice::rules(), $data);
            $model = ProductPrice::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created ProductPrice';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
