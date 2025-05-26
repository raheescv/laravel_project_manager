<?php

namespace App\Actions\PurchaseReturn;

use App\Models\PurchaseReturn;

class DeleteAction
{
    public function execute($id, $user_id)
    {
        try {
            $model = PurchaseReturn::find($id);
            if (! $model) {
                throw new \Exception("PurchaseReturn not found with the specified ID: $id.", 1);
            }
            if ($model->status == 'completed') {
                throw new \Exception('Completed purchase return cant be deleted', 1);
            }
            $model->items()->update(['deleted_by' => $user_id]);
            $model->items()->delete();

            $model->payments()->update(['deleted_by' => $user_id]);
            $model->payments()->delete();

            $model->update(['deleted_by' => $user_id]);

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the PurchaseReturn. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update PurchaseReturn';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
