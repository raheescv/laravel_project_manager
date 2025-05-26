<?php

namespace App\Actions\PurchaseReturn\Item;

use App\Models\PurchaseReturnItem;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PurchaseReturnItem::find($id);
            if (! $model) {
                throw new \Exception("PurchaseReturnItem not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the PurchaseReturnItem. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update PurchaseReturnItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
