<?php

namespace App\Actions\Sale;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;

class StockUpdateAction
{
    public function execute($sale, $user_id, $sale_type = 'sale')
    {
        try {
            foreach ($sale->items as $item) {
                $this->singleItemDelete($item, $sale, $sale_type, $user_id);
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

    public function singleItemDelete($item, $sale, $sale_type, $user_id)
    {
        $inventory = Inventory::find($item->inventory_id);
        if (! $inventory) {
            throw new \Exception('inventory not found', 1);
        }
        $inventory = $inventory->toArray();
        switch ($sale_type) {
            case 'sale':
                $inventory['quantity'] -= $item->quantity;
                $inventory['remarks'] = 'Sale:'.$sale->invoice_no;
                break;
            case 'cancel':
                $inventory['quantity'] += $item->quantity;
                $inventory['remarks'] = 'Sale Cancelled:'.$sale->invoice_no;
                break;
            case 'sale_reversal':
                $inventory['quantity'] += $item->quantity;
                $inventory['remarks'] = 'Sale Adjustment Reversal:'.$sale->invoice_no;
                break;
            case 'delete_item':
                $inventory['quantity'] += $item->quantity;
                $inventory['remarks'] = 'Sale Item Delete:'.$sale->invoice_no;
                break;
        }
        $inventory['model'] = 'Sale';
        $inventory['model_id'] = $sale->id;
        $inventory['updated_by'] = $user_id;

        $response = (new UpdateAction())->execute($inventory, $inventory['id']);
        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }
    }
}
