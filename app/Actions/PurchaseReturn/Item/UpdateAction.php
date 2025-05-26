<?php

namespace App\Actions\PurchaseReturn\Item;

use App\Models\PurchaseReturnItem;

class UpdateAction
{
    public function execute($data, $id, $user_id)
    {
        try {
            $data['updated_by'] = $user_id;
            $model = PurchaseReturnItem::find($id);
            if (! $model) {
                throw new \Exception("PurchaseReturnItem not found with the specified ID: $id.", 1);
            }
            validationHelper(PurchaseReturnItem::rules($id), $data);
            $model->update($data);

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
