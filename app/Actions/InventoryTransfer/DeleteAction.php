<?php

namespace App\Actions\InventoryTransfer;

use App\Models\InventoryTransfer;

class DeleteAction
{
    public function execute($id, $userId)
    {
        try {
            $model = InventoryTransfer::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }

            if ($model->status == 'completed') {
                throw new \Exception('Completed inventory transfer cant be deleted', 1);
            }
            $model->items()->update(['deleted_by' => $userId]);
            $model->items()->delete();

            $model->update(['deleted_by' => $userId]);

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the InventoryTransfer. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update InventoryTransfer';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
