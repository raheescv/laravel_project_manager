<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Product::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            $validator = Validator::make($data, Product::rules($id));
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Update Product';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
