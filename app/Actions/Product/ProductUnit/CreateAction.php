<?php

namespace App\Actions\Product\ProductUnit;

use App\Models\ProductUnit;

class CreateAction
{
    public function execute($data)
    {
        try {
            if(!$data['barcode']){
                $data['barcode'] = generateBarcode();
            }
            $duplicate = ProductUnit::where('product_id', $data['product_id'])->where('sub_unit_id', $data['sub_unit_id'])->exists();
            if ($duplicate) {
                throw new \Exception('Product Unit already exists for this product and sub unit.', 1);
            }
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
