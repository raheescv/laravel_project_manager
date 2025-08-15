<?php

namespace App\Actions\Purchase;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;

class StockUpdateAction
{
    public function execute($purchase, $user_id, $purchase_type = 'purchase')
    {
        try {
            foreach ($purchase->items as $item) {
                $this->singleItem($item, $purchase, $purchase_type, $user_id);
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

    public function singleItem($item, $purchase, $purchase_type, $user_id)
    {
        $inventory = Inventory::where('product_id', $item->product_id);
        if ($item->batch) {
            // $inventory = $inventory->where('batch', $item->batch);
        }
        $inventory = $inventory->where('branch_id', $purchase->branch_id);
        $inventory = $inventory->first();
        if (! $inventory) {
            throw new \Exception('inventory not found', 1);
        }
        $inventory = $inventory->toArray();
        switch ($purchase_type) {
            case 'purchase':
                $inventory['quantity'] += $item->quantity;
                $inventory['remarks'] = 'Purchase:'.$purchase->invoice_no;
                break;
            case 'cancel':
                $inventory['quantity'] -= $item->quantity;
                $inventory['remarks'] = 'Purchase Cancelled:'.$purchase->invoice_no;
                break;
            case 'purchase_reversal':
                $inventory['quantity'] -= $item->quantity;
                $inventory['remarks'] = 'Purchase Adjustment Reversal:'.$purchase->invoice_no;
                break;
            case 'delete_item':
                $inventory['quantity'] -= $item->quantity;
                $inventory['remarks'] = 'Purchase Item Delete:'.$purchase->invoice_no;
                break;
        }
        $inventory['model'] = 'Purchase';
        $inventory['model_id'] = $purchase->id;
        $inventory['updated_by'] = $user_id;

        $response = (new UpdateAction())->execute($inventory, $inventory['id']);
        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }
    }
}
