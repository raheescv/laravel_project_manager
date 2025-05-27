<?php

namespace App\Actions\User;

use App\Models\User;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = User::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            validationHelper(User::updateRules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated User';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
