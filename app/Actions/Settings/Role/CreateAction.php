<?php

namespace App\Actions\Settings\Role;

use App\Models\Role;
use App\Services\TenantService;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            $data['tenant_id'] ??= app(TenantService::class)->getCurrentTenantId() ?? 1;
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
