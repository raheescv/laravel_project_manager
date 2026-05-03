<?php

namespace App\Actions\Sale;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use App\Support\Sale\OutOfStockSales;
use Exception;

class StockUpdateAction
{
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
        $inventory = Inventory::find($item->inventory_id);
        if (! $inventory) {
            throw new Exception('inventory not found', 1);
        }
        $inventory = $inventory->toArray();
        switch ($sale_type) {
            case 'sale':
                $inventory['quantity'] -= $item->base_unit_quantity;
                $inventory['remarks'] = 'Sale:'.$sale->invoice_no;
                break;
            case 'cancel':
                $inventory['quantity'] += $item->base_unit_quantity;
                $inventory['remarks'] = 'Sale Cancelled:'.$sale->invoice_no;
                break;
            case 'sale_reversal':
                $inventory['quantity'] += $item->base_unit_quantity;
                $inventory['remarks'] = 'Sale Adjustment Reversal:'.$sale->invoice_no;
                break;
            case 'delete_item':
                $inventory['quantity'] += $item->base_unit_quantity;
                $inventory['remarks'] = 'Sale Item Delete:'.$sale->invoice_no;
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

        $requiredQuantities = $sale->items
            ->groupBy('inventory_id')
            ->map(fn ($items) => (float) $items->sum('base_unit_quantity'));

        $inventories = Inventory::withoutGlobalScopes()
            ->whereIn('id', $requiredQuantities->keys())
            ->get()
            ->keyBy('id');

        foreach ($requiredQuantities as $inventoryId => $requiredQuantity) {
            $inventory = $inventories->get($inventoryId);
            if (! $inventory) {
                throw new Exception('inventory not found', 1);
            }

            if ((float) $inventory->quantity < $requiredQuantity) {
                $item = $sale->items->firstWhere('inventory_id', $inventoryId);
                $productName = $item?->product?->name ?? 'selected item';

                throw new Exception(
                    "Insufficient stock for {$productName}. Available: {$inventory->quantity}, required: {$requiredQuantity}.",
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
