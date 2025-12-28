<?php

namespace App\Actions\PackageCategory;

use App\Models\PackageCategory;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = PackageCategory::find($id);
            if (! $model) {
                throw new \Exception("Package Category not found with the specified ID: $id.", 1);
            }

            validationHelper(PackageCategory::rules($id), $data);

            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update Package Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

