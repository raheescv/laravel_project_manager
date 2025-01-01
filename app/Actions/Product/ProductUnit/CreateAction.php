<?php

namespace App\Actions\Product\ProductUnit;

use App\Models\ProductUnit;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(ProductUnit::rules(), $data);
            $model = ProductUnit::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created ProductUnit';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
