<?php

namespace App\Actions\Settings\Role;

use Spatie\Permission\Models\Role;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            $model = Role::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Role';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
