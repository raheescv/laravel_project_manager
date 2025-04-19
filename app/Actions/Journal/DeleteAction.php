<?php

namespace App\Actions\Journal;

use App\Models\Journal;

class DeleteAction
{
    public function execute($id, $userId)
    {
        try {
            $model = Journal::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            $model->entries()->update(['deleted_by' => $userId]);
            $model->entries()->delete();

            $model->update(['deleted_by' => $userId]);
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Account. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Account';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
