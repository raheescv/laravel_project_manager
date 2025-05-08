<?php

namespace App\Actions\Sale\Package;

use App\Models\SalePackage;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(SalePackage::rules(), $data);

            $model = SalePackage::create($data);

            foreach ($data['items'] as $value) {
                SalePackage::addPackageId($data['sale_id'], $value['inventory_id'], $value['employee_id'], $model->id);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created SalePackage';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
