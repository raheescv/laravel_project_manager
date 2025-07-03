<?php

namespace App\Actions\Settings\Department;

use App\Models\Department;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Department::find($id);
            if (! $model) {
                throw new \Exception("Department not found with the specified ID: $id.", 1);
            }

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Department. Please try again.', 1);
            }

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
