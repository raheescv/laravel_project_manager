<?php

namespace App\Actions\Sale\Payment;

use App\Models\SalePayment;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = SalePayment::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the SalePayment. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update SalePayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
