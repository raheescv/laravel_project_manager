<?php

namespace App\Actions\Sale\Item;

use App\Models\SaleItem;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            $duplicate = SaleItem::where('product_id', $data['product_id'])->where('sale_id', $data['sale_id'])->exists();
            if ($duplicate) {
                throw new \Exception('Item already exists for this product under employee.', 1);
            }
            validationHelper(SaleItem::rules(), $data);
            $model = SaleItem::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created SaleItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
