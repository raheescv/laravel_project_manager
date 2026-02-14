<?php

namespace App\Actions\Tailoring\Payment;

use App\Models\TailoringPayment;
use Exception;

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
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
