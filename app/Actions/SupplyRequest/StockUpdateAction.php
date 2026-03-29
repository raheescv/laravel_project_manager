<?php

namespace App\Actions\SupplyRequest;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use App\Models\SupplyRequest;

class StockUpdateAction
{
    public function execute(SupplyRequest $supplyRequest, int $userId, string $type = 'complete'): array
    {
        try {
            $supplyRequest->load('items');

            foreach ($supplyRequest->items as $item) {
                $this->singleItem($item, $supplyRequest, $type, $userId);
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

    protected function singleItem($item, SupplyRequest $supplyRequest, string $type, int $userId): void
    {
        $inventory = Inventory::query()
            ->withoutGlobalScopes()
            ->where('product_id', $item->product_id)
            ->where('branch_id', $item->branch_id ?? $supplyRequest->branch_id)
            ->whereNull('employee_id')
            ->first();

        if (! $inventory) {
            throw new \Exception("Inventory not found for product ID: {$item->product_id} in branch ID: ".($item->branch_id ?? $supplyRequest->branch_id));
        }

        $data = $inventory->toArray();
        $data['model'] = 'SupplyRequest';
        $data['model_id'] = $supplyRequest->id;
        $data['updated_by'] = $userId;

        switch ($type) {
            case 'complete':
                if ($supplyRequest->type === 'Add') {
                    $data['quantity'] -= $item->quantity;
                    $data['remarks'] = "SupplyRequest:{$supplyRequest->order_no} [Stock Out - Add]";
                } else {
                    $data['quantity'] += $item->quantity;
                    $data['remarks'] = "SupplyRequest:{$supplyRequest->order_no} [Stock In - Return]";
                }
                break;

            case 'reversal':
                if ($supplyRequest->type === 'Add') {
                    $data['quantity'] += $item->quantity;
                    $data['remarks'] = "SupplyRequest:{$supplyRequest->order_no} [Reversal - Add]";
                } else {
                    $data['quantity'] -= $item->quantity;
                    $data['remarks'] = "SupplyRequest:{$supplyRequest->order_no} [Reversal - Return]";
                }
                break;
        }

        $response = (new UpdateAction())->execute($data, $inventory->id);
        if (! $response['success']) {
            throw new \Exception($response['message']);
        }
    }
}
