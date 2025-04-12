<?php

namespace App\Actions\InventoryTransfer;

use App\Actions\Product\Inventory\CreateAction;
use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use Exception;

class StockUpdateAction
{
    public function execute($model, $userId, $type = 'transfer')
    {
        try {
            foreach ($model->items as $item) {
                $fromInventory = Inventory::find($item->inventory_id);
                if (! $fromInventory) {
                    throw new Exception('inventory not found', 1);
                }
                switch ($type) {
                    case 'transfer':
                        $this->singleItemFunction($fromInventory, $item->quantity, $model->id, 'transfer_from', $userId);

                        $toInventory = $this->toBranchCheckFunction($fromInventory, $model->to_branch_id, $userId);
                        $this->singleItemFunction($toInventory, $item->quantity, $model->id, 'transfer_to', $userId);

                        break;
                    case 'transfer_reversal':
                        $toInventory = $this->toBranchCheckFunction($fromInventory, $model->to_branch_id, $userId);
                        $this->singleItemFunction($toInventory, $item->quantity, $model->id, 'transfer_reversal_from', $userId);

                        $this->singleItemFunction($fromInventory, $item->quantity, $model->id, 'transfer_reversal_to', $userId);

                        break;
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

    public function toBranchCheckFunction($fromInventory, $branchId, $userId)
    {
        $toInventory = Inventory::query()
            ->where('product_id', $fromInventory->product_id)
            ->where('branch_id', $branchId)
            ->first();
        if (! $toInventory) {
            $data = $fromInventory->toArray();
            $data['branch_id'] = $branchId;
            if (! isset($data['barcode'])) {
                $data['barcode'] = generateBarcode();
            }
            $data['created_by'] = $data['updated_by'] = $userId;
            $response = (new CreateAction())->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $toInventory = $response['data'];
        }

        return $toInventory;
    }

    public function singleItemFunction($inventory, $quantity, $modelId, $type, $userId)
    {
        $inventory = $inventory->toArray();

        $inventory['model'] = 'InventoryTransfer';
        $inventory['model_id'] = $modelId;
        $inventory['updated_by'] = $userId;

        switch ($type) {
            case 'transfer_from':
                $inventory['quantity'] -= $quantity;
                $inventory['remarks'] = 'InventoryTransfer:'.$modelId;
                break;
            case 'transfer_to':
                $inventory['quantity'] += $quantity;
                $inventory['remarks'] = 'InventoryTransfer:'.$modelId;
                break;

            case 'transfer_reversal_from':
                $inventory['quantity'] -= $quantity;
                $inventory['remarks'] = 'InventoryTransfer Adjustment Reversal:'.$modelId;
                break;
            case 'transfer_reversal_to':
                $inventory['quantity'] += $quantity;
                $inventory['remarks'] = 'InventoryTransfer Adjustment Reversal:'.$modelId;
                break;

            case 'delete_item_from':
                $inventory['quantity'] -= $quantity;
                $inventory['remarks'] = 'InventoryTransfer Item Delete:'.$modelId;
                break;
            case 'delete_item_to':
                $inventory['quantity'] += $quantity;
                $inventory['remarks'] = 'InventoryTransfer Item Delete:'.$modelId;
                break;
        }
        $response = (new UpdateAction())->execute($inventory, $inventory['id']);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }
}
