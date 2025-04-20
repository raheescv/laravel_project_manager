<?php

namespace App\Actions\Account;

use App\Models\Account;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Account::find($id);
            if ($model->is_locked) {
                throw new \Exception("You can't delete this account; it's locked", 1);
            }
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Account. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update Account';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
