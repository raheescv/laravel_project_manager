<?php

namespace App\Actions\PurchaseReturn\Item;

use App\Models\PurchaseReturnItem;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            $data['purchase_item_id'] = intval($data['purchase_item_id'] ?? 0);

            $data['created_by'] = $data['updated_by'] = $user_id;
            $duplicate = PurchaseReturnItem::where('product_id', $data['product_id'])->where('purchase_return_id', $data['purchase_return_id'])->exists();
            if ($duplicate) {
                throw new \Exception('Item already exists for this product under employee.', 1);
            }

            validationHelper(PurchaseReturnItem::rules(), $data);
            $model = PurchaseReturnItem::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created PurchaseReturnItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
