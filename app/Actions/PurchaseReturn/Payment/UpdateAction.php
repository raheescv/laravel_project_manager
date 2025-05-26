<?php

namespace App\Actions\PurchaseReturn\Payment;

use App\Models\PurchaseReturnPayment;

class UpdateAction
{
    public function execute($data, $id, $user_id)
    {
        try {
            $data['updated_by'] = $user_id;
            $model = PurchaseReturnPayment::find($id);
            if (! $model) {
                throw new \Exception("PurchaseReturnPayment not found with the specified ID: $id.", 1);
            }
            validationHelper(PurchaseReturnPayment::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update PurchaseReturnPayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
