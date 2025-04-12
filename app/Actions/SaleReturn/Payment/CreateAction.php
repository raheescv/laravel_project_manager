<?php

namespace App\Actions\SaleReturn\Payment;

use App\Models\SaleReturnPayment;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            validationHelper(SaleReturnPayment::rules(), $data);
            $model = SaleReturnPayment::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created SaleReturnPayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
