<?php

namespace App\Actions\Package;

use App\Models\Package;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Package::find($id);
            if (! $model) {
                throw new \Exception("Package not found with the specified ID: $id.", 1);
            }

            validationHelper(Package::rules($id), $data);

            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update Package';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

