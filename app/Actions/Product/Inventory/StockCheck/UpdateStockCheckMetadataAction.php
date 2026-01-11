<?php

namespace App\Actions\Product\Inventory\StockCheck;

use App\Models\StockCheck;
use Exception;
use Illuminate\Support\Facades\DB;

class UpdateStockCheckMetadataAction
{
    public function execute(int $stockCheckId, array $data, int $userId): array
    {
        try {
            DB::beginTransaction();

            $stockCheck = StockCheck::findOrFail($stockCheckId);

            // Update only metadata fields
            $stockCheck->branch_id = $data['branch_id'];
            $stockCheck->title = $data['title'];
            $stockCheck->date = $data['date'];
            $stockCheck->description = $data['description'] ?? null;
            $stockCheck->updated_by = $userId;
            $stockCheck->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Stock check updated successfully',
                'data' => [
                    'id' => $stockCheck->id,
                    'title' => $stockCheck->title,
                    'date' => $stockCheck->date,
                    'branch_id' => $stockCheck->branch_id,
                    'description' => $stockCheck->description,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Failed to update stock check: '.$e->getMessage(),
                'data' => [],
            ];
        }
    }
}
