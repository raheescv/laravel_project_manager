<?php

namespace App\Actions\SaleReturn;

use App\Models\SaleReturn;
use Exception;

class DeleteAction
{
    public function execute($id, $user_id)
    {
        try {
            $model = SaleReturn::find($id);
            if (! $model) {
                throw new Exception("Resource not found with the specified ID: $id.", 1);
            }

            if ($model->status == 'completed') {
                $response = (new JournalDeleteAction())->execute($model, $user_id);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                $response = (new StockUpdateAction())->execute($model, $user_id, 'delete');
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }
            $model->items()->update(['deleted_by' => $user_id]);
            $model->items()->delete();

            $model->update(['deleted_by' => $user_id]);

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Sale. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update Sale';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
