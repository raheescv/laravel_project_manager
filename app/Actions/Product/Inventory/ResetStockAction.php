<?php

namespace App\Actions\Product\Inventory;

use App\Models\Inventory;
use Exception;
use Illuminate\Support\Facades\DB;

class ResetStockAction
{
    public function execute(array $filters, string $reason, int $userId): array
    {
        try {
            DB::beginTransaction();

            // Get all inventory items based on filters
            $inventories = (new GetAction())->execute($filters)->get(['inventories.id']);
            $updatedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($inventories as $inventory) {
                try {
                    $inventory = Inventory::find($inventory->id);
                    $inventoryData = $inventory->toArray();
                    $inventoryData['quantity'] = 0;
                    $inventoryData['remarks'] = 'Stock Reset: '.$reason;
                    $inventoryData['model'] = 'StockReset';
                    $inventoryData['model_id'] = null;
                    $inventoryData['updated_by'] = $userId;
                    $response = (new UpdateAction())->execute($inventoryData, $inventory->id);
                    if ($response['success']) {
                        $updatedCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Inventory ID {$inventory->id}: {$response['message']}";
                    }
                } catch (Exception $e) {
                    $failedCount++;
                    $errors[] = "Inventory ID {$inventory->id}: {$e->getMessage()}";
                }
            }

            DB::commit();

            $message = "Successfully reset {$updatedCount} inventory item(s) to 0.";
            if ($failedCount > 0) {
                $message .= " {$failedCount} item(s) failed to update.";
            }

            return [
                'success' => $updatedCount > 0,
                'message' => $message,
                'data' => [
                    'updated_count' => $updatedCount,
                    'failed_count' => $failedCount,
                    'total_count' => $inventories->count(),
                    'errors' => $errors,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Failed to reset stock: '.$e->getMessage(),
                'data' => [],
            ];
        }
    }
}
