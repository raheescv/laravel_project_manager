<?php

namespace App\Actions\Product\Inventory;

use App\Events\InventoryActionOccurred;
use App\Models\Inventory;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(Inventory::rules(), $data);
            $model = Inventory::firstOrCreate([
                'product_id' => $data['product_id'],
                'branch_id' => $data['branch_id'],
            ], $data);
            event(new InventoryActionOccurred('create', $model));

            $return['success'] = true;
            $return['message'] = 'Successfully Created Inventory';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
