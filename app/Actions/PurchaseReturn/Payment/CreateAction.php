<?php

namespace App\Actions\PurchaseReturn\Payment;

use App\Models\PurchaseReturnPayment;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            validationHelper(PurchaseReturnPayment::rules(), $data);
            $model = PurchaseReturnPayment::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created PurchaseReturnPayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
