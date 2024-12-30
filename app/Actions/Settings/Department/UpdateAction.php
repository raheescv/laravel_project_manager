<?php

namespace App\Actions\Settings\Department;

use App\Models\Department;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Department::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }

            $data['name'] = trim($data['name']);
            validationHelper(Department::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update Department';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
