<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Tenant::find($id);
            if (! $model) {
                throw new \Exception("Tenant not found with the specified ID: $id.", 1);
            }
            validationHelper(Tenant::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Tenant';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
