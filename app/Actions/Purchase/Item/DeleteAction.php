<?php

namespace App\Actions\Purchase\Item;

use App\Models\PurchaseItem;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PurchaseItem::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the PurchaseItem. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update PurchaseItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
