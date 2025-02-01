<?php

namespace App\Actions\Product\ProductPrice;

use App\Models\ProductPrice;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = ProductPrice::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the ProductPrice. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update ProductPrice';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
