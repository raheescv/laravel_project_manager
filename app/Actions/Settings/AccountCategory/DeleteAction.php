<?php

namespace App\Actions\Settings\AccountCategory;

use App\Models\AccountCategory;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = AccountCategory::find($id);
            if (! $model) {
                throw new \Exception("AccountCategory not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the AccountCategory. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted AccountCategory';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
