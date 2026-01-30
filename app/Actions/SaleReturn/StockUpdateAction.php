<?php

namespace App\Actions\SaleReturn;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;

class StockUpdateAction
{
    public function execute($model, $user_id, $method = 'sale_return')
    {
        try {
            foreach ($model->items as $item) {
                $this->singleItemDelete($item, $model, $method, $user_id);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Inventory';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    public function singleItemDelete($item, $model, $method, $user_id)
    {
        $inventory = Inventory::find($item->inventory_id);
        if (! $inventory) {
            throw new \Exception('inventory not found', 1);
        }
        $inventory = $inventory->toArray();
        switch ($method) {
            case 'sale_return':
                $inventory['quantity'] += $item->base_unit_quantity;
                $inventory['remarks'] = 'Sale Return:'.$model->id;
                break;
            case 'sale_return_reversal':
                $inventory['quantity'] -= $item->base_unit_quantity;
                $inventory['remarks'] = 'Sale Return Adjustment Reversal:'.$model->id;
                break;
            case 'delete_item':
                $inventory['quantity'] -= $item->base_unit_quantity;
                $inventory['remarks'] = 'Sale Return Item Delete:'.$model->id;
                break;
            case 'delete':
                $inventory['quantity'] -= $item->base_unit_quantity;
                $inventory['remarks'] = 'Sale Return Delete:'.$model->id;
                break;
        }
        $inventory['model'] = 'SaleReturn';
        $inventory['model_id'] = $model->id;
        $inventory['updated_by'] = $user_id;

        $response = (new UpdateAction())->execute($inventory, $inventory['id']);
        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }
    }
}
