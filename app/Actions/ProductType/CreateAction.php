<?php

namespace App\Actions\ProductType;

use App\Models\ProductType;
use Illuminate\Support\Facades\Validator;

class CreateAction
{
    public function execute($data)
    {
        try {
            $validator = Validator::make($data, ProductType::rules());
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
            $model = ProductType::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created ProductType';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
