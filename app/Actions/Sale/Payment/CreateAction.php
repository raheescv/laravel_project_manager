<?php

namespace App\Actions\Sale\Payment;

use App\Models\Sale;
use App\Models\SalePayment;

class CreateAction
{
    public function execute(array $data, int $user_id): array
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            validationHelper(SalePayment::rules(), $data);
            $model = SalePayment::create($data);

            // Update sale payment methods
            Sale::updateSalePaymentMethods($model->sale);

            $return['success'] = true;
            $return['message'] = 'Successfully Created SalePayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
