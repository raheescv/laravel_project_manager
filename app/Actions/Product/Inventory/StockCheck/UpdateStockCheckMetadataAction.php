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

            $stockCheckData = [
                'branch_id' => $data['branch_id'],
                'title' => $data['title'],
                'date' => $data['date'],
                'description' => $data['description'] ?? null,
                'updated_by' => $userId,
            ];
            $stockCheck->update($stockCheckData);

            DB::commit();

            $return['success'] = true;
            $return['message'] = 'Stock check updated successfully';
            $return['data'] = $stockCheck->toArray();
        } catch (Exception $e) {
            DB::rollBack();

            $return['success'] = false;
            $return['message'] = 'Failed to update stock check: '.$e->getMessage();
            $return['data'] = [];
        }

        return $return;
    }
}
