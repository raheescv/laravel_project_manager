<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class CreateAction
{
    public function execute($data)
    {
        try {
            $validator = Validator::make($data, Product::rules());
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
            $model = Product::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Product';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
