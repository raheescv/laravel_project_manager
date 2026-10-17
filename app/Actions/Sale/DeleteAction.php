<?php

namespace App\Actions\Sale;

use App\Models\Sale;
use App\Models\User;

class DeleteAction
{
    public function execute($id, $user_id)
    {
        try {
            $model = Sale::find($id);
            if (! $model) {
                throw new \Exception("Sale not found with the specified ID: $id.", 1);
            }

            if ($model->status == 'completed') {
                if (! User::find($user_id)?->is_super_admin) {
                    throw new \Exception('Completed sale cant be deleted', 1);
                }
                $this->reverseCompletedSale($model, $user_id);
            }
            $model->items()->update(['deleted_by' => $user_id]);
            $model->items()->delete();

            $model->payments()->update(['deleted_by' => $user_id]);
            $model->payments()->delete();

            $model->update(['deleted_by' => $user_id]);

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Sale. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Sale';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * A completed sale has already taken stock out and posted its journals, so
     * both are undone before the sale itself goes.
     */
    private function reverseCompletedSale(Sale $model, $user_id): void
    {
        $response = (new StockUpdateAction())->execute($model, $user_id, 'delete');
        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }
        $response = (new JournalDeleteAction())->execute($model, $user_id);
        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }
    }
}
