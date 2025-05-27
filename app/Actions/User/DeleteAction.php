<?php

namespace App\Actions\User;

use App\Models\User;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = User::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the User. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted User';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
