<?php

namespace App\Actions\Product\Inventory\StockCheck;

use App\Models\StockCheck;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Changes the stock check's own (header) status — pending / completed /
 * cancelled. Item statuses are untouched: only marking an *item* completed
 * reconciles real inventory, so moving the header never moves stock.
 */
class UpdateStockCheckStatusAction
{
    public function execute(int $stockCheckId, string $status, int $userId): array
    {
        try {
            if (! array_key_exists($status, stockCheckStatuses())) {
                throw new Exception('Invalid stock check status: '.$status);
            }

            DB::beginTransaction();

            $stockCheck = StockCheck::findOrFail($stockCheckId);
            $stockCheck->update([
                'status' => $status,
                'updated_by' => $userId,
            ]);

            DB::commit();

            $return['success'] = true;
            $return['message'] = 'Status changed to '.stockCheckStatuses()[$status];
            $return['data'] = $stockCheck->toArray();
        } catch (Exception $e) {
            DB::rollBack();

            $return['success'] = false;
            $return['message'] = 'Failed to update stock check status: '.$e->getMessage();
            $return['data'] = [];
        }

        return $return;
    }
}
