<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(Tenant::rules(), $data);
            $model = Tenant::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Tenant';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
