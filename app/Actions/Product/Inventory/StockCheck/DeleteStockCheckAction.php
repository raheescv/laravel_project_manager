<?php

namespace App\Actions\Product\Inventory\StockCheck;

use App\Models\StockCheck;
use Exception;
use Illuminate\Support\Facades\DB;

class DeleteStockCheckAction
{
    public function execute(int $stockCheckId): array
    {
        try {
            DB::beginTransaction();

            $stockCheck = StockCheck::with('items')->findOrFail($stockCheckId);

            // Delete all related items first
            $stockCheck->items()->delete();

            // Delete the stock check
            $stockCheck->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Stock check and all related items deleted successfully',
                'data' => [],
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Failed to delete stock check: '.$e->getMessage(),
                'data' => [],
            ];
        }
    }
}
