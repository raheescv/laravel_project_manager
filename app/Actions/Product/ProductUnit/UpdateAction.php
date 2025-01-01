<?php

namespace App\Actions\Product\ProductUnit;

use App\Models\ProductUnit;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = ProductUnit::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            validationHelper(ProductUnit::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update ProductUnit';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
