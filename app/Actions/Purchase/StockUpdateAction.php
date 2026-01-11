<?php

namespace App\Actions\Purchase;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;

class StockUpdateAction
{
    public function execute($purchase, $user_id, $purchase_type = 'purchase')
    {
        try {
            foreach ($purchase->items as $item) {
                $this->singleItem($item, $purchase, $purchase_type, $user_id);
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

    public function singleItem($item, $purchase, $purchase_type, $user_id)
    {
        $inventory = $this->findInventory($item, $purchase);

        $oldQuantity = $inventory->quantity;
        $oldCost = $inventory->cost;

        $inventoryData = $inventory->toArray();
        $this->updateInventoryForPurchaseType($inventoryData, $item, $purchase, $purchase_type, $oldQuantity, $oldCost);
        $this->setInventoryMetadata($inventoryData, $purchase, $user_id);

        $this->saveInventory($inventoryData, $inventory->id);
    }

    private function findInventory($item, $purchase)
    {
        $query = Inventory::where('product_id', $item->product_id)
            ->where('branch_id', $purchase->branch_id);

        // TODO: Uncomment when batch tracking is implemented
        // if ($item->batch) {
        //     $query->where('batch', $item->batch);
        // }

        $inventory = $query->first();

        if (! $inventory) {
            throw new \Exception('Inventory not found for product ID: '.$item->product_id, 1);
        }

        return $inventory;
    }

    private function updateInventoryForPurchaseType(array &$inventoryData, $item, $purchase, $purchase_type, $oldQuantity, $oldCost)
    {
        switch ($purchase_type) {
            case 'purchase':
                $this->handlePurchase($inventoryData, $item, $purchase, $oldQuantity, $oldCost);
                break;
            case 'cancel':
            case 'purchase_reversal':
            case 'delete_item':
                $this->handlePurchaseReversal($inventoryData, $item, $purchase, $purchase_type, $oldQuantity, $oldCost);
                break;
        }
    }

    private function handlePurchase(array &$inventoryData, $item, $purchase, $oldQuantity, $oldCost)
    {
        $quantity = $item->quantity * ($item->conversion_factor ?? 1);
        $inventoryData['quantity'] += $quantity;
        $unit_price = round($item->unit_price * ($item->conversion_factor ?? 1), 2);
        $inventoryData['cost'] = $this->calculateWeightedAverageCost(
            $oldCost,
            $oldQuantity,
            $unit_price,
            $quantity,
            $inventoryData['quantity']
        );
        $inventoryData['remarks'] = $this->getRemarks('Purchase', $purchase->invoice_no);
    }

    private function handlePurchaseReversal(array &$inventoryData, $item, $purchase, $purchase_type, $oldQuantity, $oldCost)
    {
        $quantity = $item->quantity * ($item->conversion_factor ?? 1);
        $inventoryData['quantity'] -= $quantity;
        $unit_price = round($item->unit_price * ($item->conversion_factor ?? 1), 2);
        $inventoryData['cost'] = $this->revertCostCalculation(
            $oldCost,
            $oldQuantity,
            $unit_price,
            $quantity,
            $inventoryData['quantity']
        );
        $inventoryData['remarks'] = $this->getRemarks($this->getRemarksPrefix($purchase_type), $purchase->invoice_no);
    }

    private function calculateWeightedAverageCost($oldCost, $oldQuantity, $purchasePrice, $purchaseQuantity, $newQuantity)
    {
        if ($newQuantity <= 0) {
            return 0;
        }

        return (($oldCost * $oldQuantity) + ($purchasePrice * $purchaseQuantity)) / $newQuantity;
    }

    private function revertCostCalculation($currentCost, $currentQuantity, $purchasePrice, $purchaseQuantity, $remainingQuantity)
    {
        if ($remainingQuantity <= 0) {
            return 0;
        }

        // Revert to cost before this purchase
        // Formula: old_cost = (current_cost × current_quantity - purchase_cost × purchase_quantity) / remaining_quantity
        return (($currentCost * $currentQuantity) - ($purchasePrice * $purchaseQuantity)) / $remainingQuantity;
    }

    private function getRemarksPrefix($purchase_type)
    {
        return match ($purchase_type) {
            'cancel' => 'Purchase Cancelled',
            'purchase_reversal' => 'Purchase Adjustment Reversal',
            'delete_item' => 'Purchase Item Delete',
            default => 'Purchase',
        };
    }

    private function getRemarks($prefix, $invoiceNo)
    {
        return $prefix.':'.$invoiceNo;
    }

    private function setInventoryMetadata(array &$inventoryData, $purchase, $user_id)
    {
        $inventoryData['model'] = 'Purchase';
        $inventoryData['model_id'] = $purchase->id;
        $inventoryData['updated_by'] = $user_id;
    }

    private function saveInventory(array $inventoryData, $inventoryId)
    {
        $response = (new UpdateAction())->execute($inventoryData, $inventoryId);

        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }
    }
}
