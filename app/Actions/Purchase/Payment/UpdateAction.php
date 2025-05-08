<?php

namespace App\Actions\Purchase\Payment;

use App\Models\PurchasePayment;

class UpdateAction
{
    public function execute($data, $id, $user_id)
    {
        try {
            $data['updated_by'] = $user_id;
            $model = PurchasePayment::find($id);
            if (! $model) {
                throw new \Exception("PurchasePayment not found with the specified ID: $id.", 1);
            }
            validationHelper(PurchasePayment::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update PurchasePayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
