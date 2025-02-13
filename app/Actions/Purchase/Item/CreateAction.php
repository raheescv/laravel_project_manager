<?php

namespace App\Actions\Purchase\Item;

use App\Models\PurchaseItem;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;
            $duplicate = PurchaseItem::where('product_id', $data['product_id'])->where('purchase_id', $data['purchase_id'])->exists();
            if ($duplicate) {
                throw new \Exception('Item already exists for this product under employee.', 1);
            }

            validationHelper(PurchaseItem::rules(), $data);
            $model = PurchaseItem::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created PurchaseItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
