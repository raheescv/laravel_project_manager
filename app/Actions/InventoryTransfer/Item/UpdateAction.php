<?php

namespace App\Actions\InventoryTransfer\Item;

use App\Models\InventoryTransferItem;

class UpdateAction
{
    public function execute($data, $id, $userId)
    {
        try {
            $data['updated_by'] = $userId;
            $model = InventoryTransferItem::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            validationHelper(InventoryTransferItem::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update InventoryTransferItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
