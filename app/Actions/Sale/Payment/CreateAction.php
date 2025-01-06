<?php

namespace App\Actions\Sale\Payment;

use App\Models\SalePayment;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            validationHelper(SalePayment::rules(), $data);
            $model = SalePayment::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created SalePayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
