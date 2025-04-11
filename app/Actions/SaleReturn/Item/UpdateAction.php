<?php

namespace App\Actions\SaleReturn\Item;

use App\Models\SaleReturnItem;

class UpdateAction
{
    public function execute($data, $id, $user_id)
    {
        try {
            $data['updated_by'] = $user_id;
            $model = SaleReturnItem::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            validationHelper(SaleReturnItem::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update SaleReturnItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
