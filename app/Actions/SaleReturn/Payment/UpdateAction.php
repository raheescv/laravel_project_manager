<?php

namespace App\Actions\SaleReturn\Payment;

use App\Models\SaleReturnPayment;

class UpdateAction
{
    public function execute($data, $id, $user_id)
    {
        try {
            $data['updated_by'] = $user_id;
            $model = SaleReturnPayment::find($id);
            if (! $model) {
                throw new \Exception("Sale Return Payment not found with the specified ID: $id.", 1);
            }
            validationHelper(SaleReturnPayment::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update SaleReturnPayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
