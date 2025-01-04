<?php

namespace App\Actions\Product\Inventory;

use App\Models\Inventory;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Inventory::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            validationHelper(Inventory::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update Inventory';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
