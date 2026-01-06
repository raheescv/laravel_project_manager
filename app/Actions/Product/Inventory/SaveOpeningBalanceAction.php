<?php

namespace App\Actions\Product\Inventory;

use App\Actions\Product\Inventory\UpdateAction as InventoryUpdateAction;
use App\Models\Inventory;
use Exception;

class SaveOpeningBalanceAction
{
    public function execute(array $items, int $userId, ?string $remarks = null): array
    {
        $results = [];

        foreach ($items as $item) {
            try {
                $data = [
                    'quantity' => $item['quantity'],
                    'remarks' => $item['remarks'] ?? ($remarks ?? 'Opening Balance Entry'),
                    'model' => 'InventoryOpeningBalance',
                    'model_id' => null,
                    'updated_by' => $userId,
                ];

                // Check if inventory exists
                $existingInventory = Inventory::withoutGlobalScopes()
                    ->where('id', $item['inventory_id'])
                    ->whereNull('employee_id')
                    ->first();

                if (! $existingInventory) {
                    throw new Exception('Inventory not found');
                }

                // Update existing inventory
                $updateData = array_merge($existingInventory->toArray(), $data);

                $action = new InventoryUpdateAction();
                $response = $action->execute($updateData, $existingInventory->id);

                if ($response['success']) {
                    $results[] = [
                        'inventory_id' => $item['inventory_id'],
                        'success' => true,
                        'message' => $response['message'] ?? 'Saved successfully',
                    ];
                } else {
                    $results[] = [
                        'inventory_id' => $item['inventory_id'],
                        'success' => false,
                        'message' => $response['message'] ?? 'Failed to save',
                    ];
                }
            } catch (Exception $e) {
                $results[] = [
                    'inventory_id' => $item['inventory_id'],
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $successCount = count(array_filter($results, fn ($r) => $r['success']));
        $totalCount = count($results);

        return [
            'success' => $successCount > 0,
            'message' => "Successfully saved {$successCount} out of {$totalCount} items",
            'data' => $results,
        ];
    }
}
