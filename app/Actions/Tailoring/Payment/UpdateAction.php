<?php

namespace App\Actions\Tailoring\Payment;

use App\Models\TailoringPayment;

class UpdateAction
{
    public function execute($id, array $data, int $user_id): array
    {
        try {
            $model = TailoringPayment::findOrFail($id);
            $data['updated_by'] = $user_id;
            validationHelper(TailoringPayment::rules($id), $data);
            $model->update($data);

            // Update order payment methods
            $model->order->updatePaymentMethods();

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Tailoring Payment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
