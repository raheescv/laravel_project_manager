<?php

namespace App\Actions\ProductType;

use App\Models\ProductType;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = ProductType::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the ProductType. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update ProductType';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
