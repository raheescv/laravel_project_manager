<?php

namespace App\Actions\Grn;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;

class StockUpdateAction
{
    public function execute($grn, $userId, $type = 'receive')
    {
        try {
            foreach ($grn->items as $item) {
                $this->processItem($item, $grn, $userId, $type);
            }

            return [
                'success' => true,
                'message' => 'Successfully Updated Inventory',
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    private function processItem($item, $grn, $userId, $type)
    {
        $inventory = Inventory::where('product_id', $item->product_id)
            ->where('branch_id', $grn->branch_id)
            ->first();

        if (! $inventory) {
            throw new \Exception('Inventory not found for product ID: '.$item->product_id);
        }

        $quantity = (float) $item->quantity;
        $inventoryData = $inventory->toArray();

        if ($type === 'receive') {
            $inventoryData['quantity'] += $quantity;
            $inventoryData['remarks'] = 'GRN Received:'.$grn->grn_no;
        } elseif ($type === 'reversal') {
            $inventoryData['quantity'] -= $quantity;
            $inventoryData['remarks'] = 'GRN Reversal:'.$grn->grn_no;
        }

        $inventoryData['model'] = 'Grn';
        $inventoryData['model_id'] = $grn->id;
        $inventoryData['updated_by'] = $userId;

        $response = (new UpdateAction())->execute($inventoryData, $inventory->id);

        if (! $response['success']) {
            throw new \Exception($response['message']);
        }
    }
}
