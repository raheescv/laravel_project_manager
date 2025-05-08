<?php

namespace App\Actions\Sale\Item;

use App\Models\SaleItem;

class UpdateAction
{
    public function execute($data, $id, $user_id)
    {
        try {
            $data['updated_by'] = $user_id;
            $model = SaleItem::find($id);
            if (! $model) {
                throw new \Exception("SaleItem not found with the specified ID: $id.", 1);
            }

            // to avoid storing the audit log
            if ($model->quantity == $data['quantity']) {
                $data['quantity'] = $model->quantity;
            }

            validationHelper(SaleItem::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update SaleItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
