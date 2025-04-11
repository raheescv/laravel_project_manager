<?php

namespace App\Actions\SaleReturn\Item;

use App\Models\SaleReturnItem;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            $duplicate = SaleReturnItem::where('product_id', $data['product_id'])->where('sale_return_id', $data['sale_return_id'])->exists();
            if ($duplicate) {
                throw new \Exception('Item already exists for this product under employee.', 1);
            }

            validationHelper(SaleReturnItem::rules(), $data);
            $model = SaleReturnItem::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created SaleReturnItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
