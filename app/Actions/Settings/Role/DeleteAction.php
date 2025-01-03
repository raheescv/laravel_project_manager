<?php

namespace App\Actions\Settings\Role;

use Spatie\Permission\Models\Role;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Role::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Role. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update Role';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}