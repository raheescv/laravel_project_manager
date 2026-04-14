<?php

namespace App\Actions\RentOut\Utility\Term;

use App\Models\RentOutUtilityTerm;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = RentOutUtilityTerm::find($id);
            if (! $model) {
                throw new \Exception("Utility Term not found with the specified ID: $id.", 1);
            }
            validationHelper(RentOutUtilityTerm::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Utility Term';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
