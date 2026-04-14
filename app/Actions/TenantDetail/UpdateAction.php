<?php

namespace App\Actions\TenantDetail;

use App\Models\TenantDetail;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = TenantDetail::find($id);
            if (! $model) {
                throw new \Exception("Tenant Detail not found with the specified ID: $id.", 1);
            }
            $data['name'] = trim($data['name']);
            validationHelper(TenantDetail::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Tenant Detail';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
