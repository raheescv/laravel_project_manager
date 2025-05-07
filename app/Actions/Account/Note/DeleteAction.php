<?php

namespace App\Actions\Account\Note;

use App\Models\AccountNote;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = AccountNote::find($id);
            if ($model->is_locked) {
                throw new \Exception("You can't delete this AccountNote; it's locked", 1);
            }
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the AccountNote. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update AccountNote';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
