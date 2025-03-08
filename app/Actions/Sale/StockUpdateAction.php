<?php

namespace App\Actions\Sale;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;

class StockUpdateAction
{
    public function execute($sale, $user_id, $is_sale = true)
    {
        try {
            foreach ($sale->items as $value) {

                $inventory = Inventory::find($value->inventory_id);
                if (! $inventory) {
                    throw new \Exception('inventory not found', 1);
                }
                $inventory = $inventory->toArray();
                if ($is_sale) {
                    $inventory['quantity'] -= $value->quantity;
                    $inventory['remarks'] = 'Sale:'.$sale->invoice_no;
                } else {
                    $inventory['quantity'] += $value->quantity;
                    $inventory['remarks'] = 'Sale Cancelled:'.$sale->invoice_no;
                }
                $inventory['model'] = 'Sale';
                $inventory['model_id'] = $sale->id;
                $inventory['updated_by'] = $user_id;

                $response = (new UpdateAction())->execute($inventory, $inventory['id']);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }

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
}
