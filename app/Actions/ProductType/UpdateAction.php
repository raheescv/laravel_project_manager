<?php

namespace App\Actions\ProductType;

use App\Models\ProductType;
use Illuminate\Support\Facades\Validator;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = ProductType::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            $validator = Validator::make($data, ProductType::rules($id));
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Update ProductType';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
