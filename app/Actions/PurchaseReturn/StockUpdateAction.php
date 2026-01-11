<?php

namespace App\Actions\PurchaseReturn;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;

class StockUpdateAction
{
    public function execute($purchaseReturn, $user_id, $is_purchase_return = true)
    {
        try {
            foreach ($purchaseReturn->items as $value) {
                $inventory = Inventory::where('product_id', $value->product_id);
                if ($value->batch) {
                    $inventory = $inventory->where('batch', $value->batch);
                }
                $inventory = $inventory->where('branch_id', $purchaseReturn->branch_id);
                $inventory = $inventory->first();
                if (! $inventory) {
                    throw new \Exception('Inventory not found '.$value->product_id, 1);
                }
                $inventory = $inventory->toArray();
                if ($is_purchase_return) {
                    $inventory['quantity'] -= round($value->quantity * ($value->conversion_factor ?? 1), 2);
                    $inventory['remarks'] = 'PurchaseReturn:'.$purchaseReturn->invoice_no;
                } else {
                    $inventory['quantity'] += round($value->quantity * ($value->conversion_factor ?? 1), 2);
                    $inventory['remarks'] = 'PurchaseReturn Cancelled:'.$purchaseReturn->invoice_no;
                }
                $inventory['model'] = 'PurchaseReturn';
                $inventory['model_id'] = $purchaseReturn->id;
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
