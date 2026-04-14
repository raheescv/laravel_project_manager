<?php

namespace App\Actions\TenantDetail;

use App\Models\TenantDetail;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            validationHelper(TenantDetail::rules(), $data, 'Tenant Detail');
            $model = TenantDetail::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Tenant Detail';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
