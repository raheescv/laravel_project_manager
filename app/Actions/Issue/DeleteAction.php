<?php

namespace App\Actions\Issue;

use App\Models\Issue;
use App\Actions\Issue\StockUpdateAction;
use Exception;

class DeleteAction
{
    public function execute(int $id, int $userId): array
    {
        try {
            $model = Issue::with('items')->find($id);
            if (! $model) {
                throw new Exception("Issue not found with ID: {$id}.", 1);
            }

            $reversal = (new StockUpdateAction())->execute($model, $userId, 'reversal');
            if (! $reversal['success']) {
                throw new Exception($reversal['message'], 1);
            }

            $model->items()->delete();
            $model->delete();

            return [
                'success' => true,
                'message' => 'Successfully deleted issue',
                'data' => $model,
            ];
        } catch (Exception $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}
