<?php

namespace App\Listeners;

use App\Events\InventoryActionOccurred;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Support\Migration\BulkImport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

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
            $log = new InventoryLog($logData);
            if ($occurredAt = $this->documentDate($newInventory->model, $newInventory->model_id)) {
                $log->created_at = $occurredAt;
                $log->updated_at = $occurredAt;
            }
            $log->save();
        }

        // During a bulk migration this weighted-average recompute would run for every sale line and
        // write products.cost, serialising parallel workers on the shared product row. Skip it here;
        // MigrateDataCommand recomputes every product's cost once after the replay finishes.
        if (BulkImport::enabled()) {
            return;
        }

        // Update product cost based on weighted average of all inventories
        $this->updateProductCost($newInventory->product_id);
    }

    /**
     * A movement belongs on the date of the document that caused it, not the moment the
     * row happened to be written: a purchase dated last month must not surface in today's
     * log just because it was completed today. Deliberately uncached — within a single
     * save the reversal is written before the document's new date is stored and the
     * re-post after it, and each row should carry the date that applied when it ran.
     */
    private function documentDate(?string $model, $modelId): ?string
    {
        if (! $model || ! $modelId) {
            return null;
        }

        $class = 'App\\Models\\'.$model;
        if (! class_exists($class)) {
            return null;
        }

        $query = $class::query()->withoutGlobalScopes();
        if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withTrashed();
        }

        $document = $query->find($modelId);
        if (! $document || ! $document->date) {
            return null;
        }

        // Keep the document's own time of day so rows written together stay in order.
        $time = $document->created_at ? $document->created_at->format('H:i:s') : '00:00:00';

        return Carbon::parse($document->date)->format('Y-m-d').' '.$time;
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
