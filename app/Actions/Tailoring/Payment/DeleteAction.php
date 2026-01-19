<?php

namespace App\Actions\Tailoring\Payment;

use App\Models\TailoringPayment;
use Exception;

class DeleteAction
{
    public function execute($id, int $user_id): array
    {
        try {
            $model = TailoringPayment::findOrFail($id);
            $order = $model->order;

            $model->deleted_by = $user_id;
            $model->save();
            $model->delete();

            // Update order payment methods
            $order->updatePaymentMethods();

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Tailoring Payment';
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
