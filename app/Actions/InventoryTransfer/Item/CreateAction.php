<?php

namespace App\Actions\InventoryTransfer\Item;

use App\Models\InventoryTransferItem;

class CreateAction
{
    public function execute($data, $userId)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $userId;
            $duplicate = InventoryTransferItem::where('inventory_id', $data['inventory_id'])
                ->where('inventory_transfer_id', $data['inventory_transfer_id'])
                ->exists();
            if ($duplicate) {
                throw new \Exception('Item already exists for this product.', 1);
            }

            validationHelper(InventoryTransferItem::rules(), $data);
            $model = InventoryTransferItem::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created InventoryTransferItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
