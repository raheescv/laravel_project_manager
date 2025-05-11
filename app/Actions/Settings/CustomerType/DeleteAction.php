<?php

namespace App\Actions\Settings\CustomerType;

use App\Models\CustomerType;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = CustomerType::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the CustomerType. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update CustomerType';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
