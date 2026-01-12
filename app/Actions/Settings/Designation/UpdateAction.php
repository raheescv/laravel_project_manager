<?php

namespace App\Actions\Settings\Designation;

use App\Models\Designation;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Designation::find($id);
            if (! $model) {
                throw new \Exception("Designation not found with the specified ID: $id.", 1);
            }
            validationHelper(Designation::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Designation';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
