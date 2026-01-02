<?php

namespace App\Listeners;

use App\Events\InventoryActionOccurred;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;

class LogInventoryAction
{
    public function handle(InventoryActionOccurred $event): void
    {
        $action = $event->action;
        $newInventory = $event->newInventory;
        $oldInventory = $event->oldInventory;

        $quantity_in = $quantity_out = 0;
        switch ($action) {
            case 'update':
                $diff = round($newInventory['quantity'] - $oldInventory['quantity'], 4);
                if ($diff > 0) {
                    $quantity_in = $diff;
                } else {
                    $quantity_out = -$diff;
                }
                break;
            case 'create':
                $quantity_in = $newInventory['quantity'];
                break;
            default:
                break;
        }
        if ($quantity_out != $quantity_in) {
            $logData = [
                'branch_id' => $newInventory->branch_id,
                'employee_id' => $newInventory->employee_id,
                'product_id' => $newInventory->product_id,
                'quantity_in' => $quantity_in,
                'quantity_out' => $quantity_out,
                'balance' => $newInventory->quantity,
                'barcode' => $newInventory->barcode,
                'batch' => $newInventory->batch,
                'cost' => $newInventory->cost,

                'remarks' => $newInventory->remarks,
                'model' => $newInventory->model,
                'model_id' => $newInventory->model_id,

                'user_id' => $newInventory->updated_by,
                'user_name' => $newInventory->updatedUser?->name,
            ];
            InventoryLog::create($logData);
        }

        // Update product cost based on weighted average of all inventories
        $this->updateProductCost($newInventory->product_id);
    }

    private function updateProductCost(int $productId): void
    {
        $averageCost = $this->calculateWeightedAverageCost($productId);

        if ($averageCost !== null) {
            Product::where('id', $productId)->update(['cost' => $averageCost]);
        }
    }

    private function calculateWeightedAverageCost(int $productId): ?float
    {
        $inventories = Inventory::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->get(['cost', 'quantity']);

        if ($inventories->isEmpty()) {
            return null;
        }

        $totalCost = $inventories->sum(fn ($inventory) => $inventory->cost * $inventory->quantity);
        $totalQuantity = $inventories->sum('quantity');

        if ($totalQuantity <= 0) {
            return null;
        }

        return round($totalCost / $totalQuantity, 2);
    }
}
