<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Tenant::find($id);
            if (! $model) {
                throw new \Exception("Tenant not found with the specified ID: $id.", 1);
            }
            $model->delete();

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Tenant';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
