<?php

namespace App\Listeners;

use App\Events\InventoryActionOccurred;
use App\Models\InventoryLog;

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
                $diff = $newInventory['quantity'] - $oldInventory['quantity'];
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
                'product_id' => $newInventory->product_id,
                'quantity_in' => $quantity_in,
                'quantity_out' => $quantity_out,
                'balance' => $newInventory->quantity,
                'barcode' => $newInventory->barcode,
                'batch' => $newInventory->batch,
                'cost' => $newInventory->cost,
                'remarks' => $newInventory->remarks,
                'user_id' => $newInventory->updated_by,
                'user_name' => $newInventory->updatedUser?->name,
            ];
            InventoryLog::create($logData);
        }
    }
}
