<?php

namespace App\Actions\Settings\Brand;

use App\Models\Brand;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Brand::find($id);
            if (! $model) {
                throw new \Exception("Brand not found with the specified ID: $id.", 1);
            }

            $data['name'] = trim($data['name']);
            validationHelper(Brand::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update Brand';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
