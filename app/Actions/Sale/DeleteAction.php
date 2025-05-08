<?php

namespace App\Actions\Sale;

use App\Models\Sale;

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
                throw new \Exception('Completed sale cant be deleted', 1);
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
            $return['message'] = 'Successfully Update Sale';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
