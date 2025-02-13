<?php

namespace App\Actions\Purchase\Item;

use App\Models\PurchaseItem;

class UpdateAction
{
    public function execute($data, $id, $user_id)
    {
        try {
            $data['updated_by'] = $user_id;
            $model = PurchaseItem::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            validationHelper(PurchaseItem::rules($id), $data);
            $model->update($data);

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
