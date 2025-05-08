<?php

namespace App\Actions\Sale\Package;

use App\Models\SaleItem;
use App\Models\SalePackage;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = SalePackage::find($id);
            if (! $model) {
                throw new \Exception("SalePackage not found with the specified ID: $id.", 1);
            }

            validationHelper(SalePackage::rules($id), $data);

            $model->update($data);

            SaleItem::where('sale_package_id', $model->id)->update(['sale_package_id' => null]);

            foreach ($data['items'] as $value) {
                SalePackage::addPackageId($data['sale_id'], $value['inventory_id'], $value['employee_id'], $model->id);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update SalePackage';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
