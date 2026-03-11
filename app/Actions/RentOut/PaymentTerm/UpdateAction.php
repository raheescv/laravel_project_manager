<?php

namespace App\Actions\RentOut\PaymentTerm;

use App\Models\RentOutPaymentTerm;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = RentOutPaymentTerm::find($id);
            if (! $model) {
                throw new \Exception("Payment Term not found with the specified ID: $id.", 1);
            }
            validationHelper(RentOutPaymentTerm::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Payment Term';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
