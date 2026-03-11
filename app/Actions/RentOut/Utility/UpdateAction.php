<?php

namespace App\Actions\RentOut\Utility;

use App\Models\RentOutUtility;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = RentOutUtility::find($id);
            if (! $model) {
                throw new \Exception("RentOut Utility not found with the specified ID: $id.", 1);
            }
            validationHelper(RentOutUtility::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Utility';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
