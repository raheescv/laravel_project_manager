<?php

namespace App\Actions\RentOut;

use App\Helpers\Facades\RentOutTransactionHelper;
use App\Models\RentOut;

class UpdateAction
{
    public function execute($data, $id, $userId = null)
    {
        try {
            $model = RentOut::find($id);
            if (! $model) {
                throw new \Exception("RentOut not found with the specified ID: $id.", 1);
            }

            validationHelper(RentOut::rules($id), $data);
            $model->update($data);

            // Handle down payment via RentOutTransaction
            if (! empty($data['down_payment']) && $data['down_payment'] > 0) {
                $model->refresh();
                $createdBy = $userId ?? $model->created_by;

                $response = RentOutTransactionHelper::storeDownPayment($model, $createdBy);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated RentOut Agreement';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
