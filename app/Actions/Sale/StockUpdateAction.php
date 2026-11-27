<?php

namespace App\Actions\Sale;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use App\Models\ProductRawMaterial;
use App\Models\Scopes\AssignedBranchScope;
use App\Support\Sale\OutOfStockSales;
use Exception;

class StockUpdateAction
{
    /** @var array<int, \Illuminate\Support\Collection<int, \App\Models\SaleItemOption>> */
    private array $stockOptions = [];

    public function execute($sale, $user_id, $sale_type = 'sale'): array
    {
        try {
            $this->assertStockIsAvailable($sale, $sale_type);

            foreach ($sale->items as $item) {
                $this->singleItem($item, $sale, $sale_type, $user_id);
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

    public function singleItem($item, $sale, $sale_type, $user_id): void
    {
        $rawMaterials = ProductRawMaterial::where('product_id', $item->product_id)->get();

        if ($rawMaterials->isNotEmpty()) {
            $this->updateRawMaterials($item, $sale, $sale_type, $user_id, $rawMaterials);
        } else {
            $this->adjustInventory(
                $item->inventory_id,
                $item->base_unit_quantity,
                $sale,
                $sale_type,
                $user_id
            );
        }

        $this->updateOptionStock($item, $sale, $sale_type, $user_id);
    }

    /**
     * Picked options that use a stock item (Red Bull → one Red Bull can) move
     * that item's stock in the sale's branch, per unit of the line.
     */
    private function updateOptionStock($item, $sale, $sale_type, $user_id): void
    {
        foreach ($this->stockOptions($item) as $option) {
            $quantity = (float) $option->stock_quantity * (float) $item->base_unit_quantity;
            if ($quantity <= 0) {
                continue;
            }
            $inventory = Inventory::withoutGlobalScope(AssignedBranchScope::class)
                ->where('product_id', $option->product_id)
                ->where('branch_id', $sale->branch_id)
                ->first();
            if (! $inventory) {
                throw new Exception("The stock item of the option {$option->option_name} is not set up in this branch.", 1);
            }

            $this->adjustInventory($inventory->id, $quantity, $sale, $sale_type, $user_id, ' (Option '.$option->option_name.')');
        }
    }

    /** A line's picked options that use a stock item, read once per run (the availability check and the movement share it). */
    private function stockOptions($item)
    {
        return $this->stockOptions[$item->id] ??= $item->options()->whereNotNull('product_id')->get();
    }

    private function updateRawMaterials($item, $sale, $sale_type, $user_id, $rawMaterials): void
    {
        foreach ($rawMaterials as $rawMaterial) {
            $consumed = (float) $rawMaterial->quantity * (float) $item->base_unit_quantity;

            $rmInventory = Inventory::withoutGlobalScope(AssignedBranchScope::class)
                ->where('product_id', $rawMaterial->raw_material_id)
                ->where('branch_id', $sale->branch_id)
                ->first();

            if (! $rmInventory) {
                throw new Exception('Raw material inventory not found for product id '.$rawMaterial->raw_material_id, 1);
            }

            $this->adjustInventory(
                $rmInventory->id,
                $consumed,
                $sale,
                $sale_type,
                $user_id,
                ' (Raw Material)'
            );
        }
    }

    private function adjustInventory($inventoryId, $quantity, $sale, $sale_type, $user_id, $suffix = ''): void
    {
        $inventory = Inventory::find($inventoryId);
        if (! $inventory) {
            throw new Exception('inventory not found', 1);
        }
        $inventory = $inventory->toArray();

        switch ($sale_type) {
            case 'sale':
                $inventory['quantity'] -= $quantity;
                $inventory['remarks'] = 'Sale'.$suffix.':'.$sale->invoice_no;
                break;
            case 'cancel':
                $inventory['quantity'] += $quantity;
                $inventory['remarks'] = 'Sale Cancelled'.$suffix.':'.$sale->invoice_no;
                break;
            case 'sale_reversal':
                $inventory['quantity'] += $quantity;
                $inventory['remarks'] = 'Sale Adjustment Reversal'.$suffix.':'.$sale->invoice_no;
                break;
            case 'delete':
                $inventory['quantity'] += $quantity;
                $inventory['remarks'] = 'Sale Deleted'.$suffix.':'.$sale->invoice_no;
                break;
            case 'delete_item':
                $inventory['quantity'] += $quantity;
                $inventory['remarks'] = 'Sale Item Delete'.$suffix.':'.$sale->invoice_no;
                break;
        }
        $inventory['model'] = 'Sale';
        $inventory['model_id'] = $sale->id;
        $inventory['updated_by'] = $user_id;

        $response = (new UpdateAction())->execute($inventory, $inventory['id']);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }

    private function assertStockIsAvailable($sale, string $saleType): void
    {
        if ($saleType !== 'sale' || ! $this->shouldPreventOutOfStockSales()) {
            return;
        }

        $this->assertProductStockIsAvailable($sale);
        $this->assertRawMaterialStockIsAvailable($sale);
    }

    private function assertProductStockIsAvailable($sale): void
    {
        // Composite products are validated against their raw material stock instead.
        $compositeProductIds = ProductRawMaterial::whereIn('product_id', $sale->items->pluck('product_id'))
            ->distinct()
            ->pluck('product_id')
            ->all();

        $requiredQuantities = $sale->items
            ->reject(fn ($item) => in_array($item->product_id, $compositeProductIds, true))
            ->groupBy('inventory_id')
            ->map(fn ($items) => (float) $items->sum('base_unit_quantity'));

        if ($requiredQuantities->isEmpty()) {
            return;
        }

        $inventories = Inventory::withoutGlobalScope(AssignedBranchScope::class)
            ->whereIn('id', $requiredQuantities->keys())
            ->with('product:id,name,type')
            ->get()
            ->keyBy('id');

        foreach ($requiredQuantities as $inventoryId => $requiredQuantity) {
            $inventory = $inventories->get($inventoryId);
            if (! $inventory) {
                throw new Exception('inventory not found', 1);
            }

            if ($inventory->product?->type !== 'product') {
                continue;
            }

            if ((float) $inventory->quantity < $requiredQuantity) {
                $productName = $inventory->product->name ?? 'selected item';

                throw new Exception("Insufficient stock for {$productName}. Available: {$inventory->quantity}, required: {$requiredQuantity}.", 1);
            }
        }
    }

    private function assertRawMaterialStockIsAvailable($sale): void
    {
        $required = [];
        foreach ($sale->items as $item) {
            $rawMaterials = ProductRawMaterial::where('product_id', $item->product_id)->get();
            foreach ($rawMaterials as $rm) {
                $quantity = (float) $rm->quantity * (float) $item->base_unit_quantity;
                $rawMaterialQty = ($required[$rm->raw_material_id] ?? 0);
                $required[$rm->raw_material_id] = $rawMaterialQty + $quantity;
            }
            // Stock items used by picked options are checked the same way.
            foreach ($this->stockOptions($item) as $option) {
                $required[$option->product_id] = ($required[$option->product_id] ?? 0) + (float) $option->stock_quantity * (float) $item->base_unit_quantity;
            }
        }

        if (empty($required)) {
            return;
        }

        $inventories = Inventory::withoutGlobalScope(AssignedBranchScope::class)
            ->whereIn('product_id', array_keys($required))
            ->where('branch_id', $sale->branch_id)
            ->with('product:id,name')
            ->get()
            ->keyBy('product_id');

        foreach ($required as $productId => $requiredQuantity) {
            $inventory = $inventories->get($productId);
            if (! $inventory) {
                throw new Exception('Raw material inventory not found for product id '.$productId, 1);
            }

            if ((float) $inventory->quantity < $requiredQuantity) {
                $productName = $inventory->product->name ?? ('product id '.$productId);

                throw new Exception(
                    "Insufficient raw material stock for {$productName}. Available: {$inventory->quantity}, required: {$requiredQuantity}.",
                    1
                );
            }
        }
    }

    private function shouldPreventOutOfStockSales(): bool
    {
        return OutOfStockSales::prevented();
    }
}
