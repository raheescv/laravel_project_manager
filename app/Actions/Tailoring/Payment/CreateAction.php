<?php

namespace App\Actions\Tailoring\Payment;

use App\Models\TailoringOrder;
use App\Models\TailoringPayment;

class CreateAction
{
    public function execute(array $data, int $user_id): array
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            validationHelper(TailoringPayment::rules(), $data);
            $model = TailoringPayment::create($data);

            // Update order payment methods
            $model->order->updatePaymentMethods();

            $return['success'] = true;
            $return['message'] = 'Successfully Created Tailoring Payment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
