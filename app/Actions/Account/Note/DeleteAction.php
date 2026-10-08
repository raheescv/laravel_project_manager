<?php

namespace App\Actions\Account\Note;

use App\Models\AccountNote;

class DeleteAction
{
    public function execute(int|string|null $id)
    {
        try {
            $model = AccountNote::find($id);
            if (! $model) {
                throw new \Exception("AccountNote not found with the specified ID: $id.", 1);
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
