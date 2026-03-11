<?php

namespace App\Actions\RentOut\Cheque;

use App\Models\RentOutCheque;

class UpdateStatusAction
{
    public function execute($id, $status)
    {
        try {
            $model = RentOutCheque::find($id);
            if (! $model) {
                throw new \Exception("RentOut Cheque not found with the specified ID: $id.", 1);
            }
            $model->update(['status' => $status]);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Cheque Status';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
