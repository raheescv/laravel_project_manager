<?php

namespace App\Actions\Settings\Utility;

use App\Models\Utility;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Utility::find($id);
            if (! $model) {
                throw new \Exception("Utility not found with the specified ID: $id.", 1);
            }
            $data['name'] = trim($data['name']);
            validationHelper(Utility::rules($id), $data);
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
