<?php

namespace App\Actions\Product\Inventory\StockCheck;

use App\Models\Inventory;
use App\Models\StockCheck;
use Exception;
use Illuminate\Support\Facades\DB;

class CreateStockCheckAction
{
    public function execute(array $data, int $userId): array
    {
        try {
            DB::beginTransaction();

            // Create StockCheck record
            $data = [
                'tenant_id' => session('tenant_id'),
                'branch_id' => $data['branch_id'],
                'title' => $data['title'],
                'date' => $data['date'],
                'description' => $data['description'] ?? null,
                'status' => 'pending',
                'created_by' => $userId,
                'updated_by' => $userId,
            ];
            $stockCheck = StockCheck::create($data);

            $columns = [
                DB::raw("{$stockCheck->id} as stock_check_id"),
                'product_id',
                DB::raw('sum(quantity) as recorded_quantity'),
            ];

            $items = Inventory::withoutGlobalScopes()
                ->where('branch_id', $stockCheck->branch_id)
                ->whereNull('employee_id')
                ->groupBy('product_id')
                ->get($columns)
                ->toArray();

            $stockCheck->items()->insert($items);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Stock check created successfully',
                'data' => [
                    'id' => $stockCheck->id,
                    'title' => $stockCheck->title,
                    'date' => $stockCheck->date,
                    'branch_id' => $stockCheck->branch_id,
                    'items_count' => count($items),
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Failed to create stock check: '.$e->getMessage(),
                'data' => [],
            ];
        }
    }
}
