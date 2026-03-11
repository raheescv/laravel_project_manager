<?php

namespace App\Actions\RentOut;

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

            // Handle down payment journal if changed
            if (! empty($data['down_payment']) && $data['down_payment'] > 0) {
                $model->refresh();
                (new JournalEntryAction())->executeDownPayment($model, $userId ?? $model->created_by);
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
