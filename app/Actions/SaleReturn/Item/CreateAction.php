<?php

namespace App\Actions\SaleReturn\Item;

use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Exception;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            $duplicate = SaleReturnItem::where('product_id', $data['product_id'])->where('sale_return_id', $data['sale_return_id'])->exists();
            if ($duplicate) {
                throw new Exception('Item already exists for this product under employee.', 1);
            }

            $employee_id = $data['employee_id'] ?? (SaleItem::find($data['sale_item_id'])->employee_id ?? '');
            $data['employee_id'] = $employee_id;

            validationHelper(SaleReturnItem::rules(), $data);
            $model = SaleReturnItem::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created SaleReturnItem';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
