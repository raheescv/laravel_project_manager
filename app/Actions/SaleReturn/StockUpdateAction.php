<?php

namespace App\Actions\SaleReturn;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use App\Models\ProductRawMaterial;

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
        $rawMaterials = ProductRawMaterial::where('product_id', $item->product_id)->get();

        if ($rawMaterials->isNotEmpty()) {
            $this->updateRawMaterials($item, $model, $method, $user_id, $rawMaterials);

            return;
        }

        $this->adjustInventory(
            $item->inventory_id,
            $item->base_unit_quantity,
            $model,
            $method,
            $user_id
        );
    }

    private function updateRawMaterials($item, $model, $method, $user_id, $rawMaterials): void
    {
        foreach ($rawMaterials as $rawMaterial) {
            $consumed = (float) $rawMaterial->quantity * (float) $item->base_unit_quantity;

            $rmInventory = Inventory::withoutGlobalScopes()
                ->where('product_id', $rawMaterial->raw_material_id)
                ->where('branch_id', $model->branch_id)
                ->first();

            if (! $rmInventory) {
                throw new \Exception('Raw material inventory not found for product id '.$rawMaterial->raw_material_id, 1);
            }

            $this->adjustInventory(
                $rmInventory->id,
                $consumed,
                $model,
                $method,
                $user_id,
                ' (Raw Material)'
            );
        }
    }

    private function adjustInventory($inventoryId, $quantity, $model, $method, $user_id, $suffix = ''): void
    {
        $inventory = Inventory::find($inventoryId);
        if (! $inventory) {
            throw new \Exception('inventory not found', 1);
        }
        $inventory = $inventory->toArray();

        switch ($method) {
            case 'sale_return':
                $inventory['quantity'] += $quantity;
                $inventory['remarks'] = 'Sale Return'.$suffix.':'.$model->id;
                break;
            case 'sale_return_reversal':
                $inventory['quantity'] -= $quantity;
                $inventory['remarks'] = 'Sale Return Adjustment Reversal'.$suffix.':'.$model->id;
                break;
            case 'delete_item':
                $inventory['quantity'] -= $quantity;
                $inventory['remarks'] = 'Sale Return Item Delete'.$suffix.':'.$model->id;
                break;
            case 'delete':
                $inventory['quantity'] -= $quantity;
                $inventory['remarks'] = 'Sale Return Delete'.$suffix.':'.$model->id;
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
