<?php

namespace App\Actions\Purchase\Payment;

use App\Models\PurchasePayment;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            validationHelper(PurchasePayment::rules(), $data);
            $model = PurchasePayment::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created PurchasePayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
