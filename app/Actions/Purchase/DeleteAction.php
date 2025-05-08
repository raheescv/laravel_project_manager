<?php

namespace App\Actions\Purchase;

use App\Models\Purchase;

class DeleteAction
{
    public function execute($id, $user_id)
    {
        try {
            $model = Purchase::find($id);
            if (! $model) {
                throw new \Exception("Purchase not found with the specified ID: $id.", 1);
            }
            if ($model->status == 'completed') {
                throw new \Exception('Completed purchase cant be deleted', 1);
            }
            $model->items()->update(['deleted_by' => $user_id]);
            $model->items()->delete();

            $model->payments()->update(['deleted_by' => $user_id]);
            $model->payments()->delete();

            $model->update(['deleted_by' => $user_id]);

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Purchase. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update Purchase';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
