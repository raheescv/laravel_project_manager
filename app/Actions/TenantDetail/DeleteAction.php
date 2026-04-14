<?php

namespace App\Actions\TenantDetail;

use App\Models\TenantDetail;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = TenantDetail::find($id);
            if (! $model) {
                throw new \Exception("Tenant Detail not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Tenant Detail. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Tenant Detail';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
