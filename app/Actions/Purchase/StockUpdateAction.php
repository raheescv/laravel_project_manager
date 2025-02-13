<?php

namespace App\Actions\Purchase;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;

class StockUpdateAction
{
    public function execute($purchase, $user_id, $is_purchase = true)
    {
        try {
            foreach ($purchase->items as $value) {
                $inventory = Inventory::where('product_id', $value->product_id);
                if ($value->batch) {
                    $inventory = $inventory->where('batch', $value->batch);
                }
                $inventory = $inventory->first();
                if (! $inventory) {
                    throw new \Exception('Inventory not found', 1);
                }
                $inventory = $inventory->toArray();
                if ($is_purchase) {
                    $inventory['quantity'] += $value->quantity;
                    $inventory['remarks'] = 'Purchase:'.$purchase->invoice_no;
                } else {
                    $inventory['quantity'] -= $value->quantity;
                    $inventory['remarks'] = 'Purchase Cancelled:'.$purchase->invoice_no;
                }
                $inventory['model'] = 'Purchase';
                $inventory['model_id'] = $purchase->id;
                $inventory['updated_by'] = $user_id;

                $response = (new UpdateAction)->execute($inventory, $inventory['id']);
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
