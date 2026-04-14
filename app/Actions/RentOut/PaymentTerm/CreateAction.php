<?php

namespace App\Actions\RentOut\PaymentTerm;

use App\Models\RentOutPaymentTerm;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(RentOutPaymentTerm::rules(), $data, 'Payment Term');
            $model = RentOutPaymentTerm::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Payment Term';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
