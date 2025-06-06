<?php

namespace App\Actions\Product\ProductUnit;

use App\Models\ProductUnit;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = ProductUnit::find($id);
            if (! $model) {
                throw new \Exception("ProductUnit not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the ProductUnit. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update ProductUnit';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
