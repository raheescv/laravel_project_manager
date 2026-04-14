<?php

namespace App\Actions\RentOut\PaymentTerm;

use App\Models\RentOutPaymentTerm;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOutPaymentTerm::find($id);
            if (! $model) {
                throw new \Exception("Payment Term not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Payment Term. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Payment Term';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
