<?php

namespace App\Actions\Settings\Role;

use Spatie\Permission\Models\Role;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Role::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            $data['name'] = trim($data['name']);
            $model->update($data);

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
