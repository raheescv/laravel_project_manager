<?php

namespace App\Actions\Issue;

use App\Models\Issue;
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

            $return['success'] = true;
            $return['message'] = 'Successfully deleted issue';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
