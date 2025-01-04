<?php

namespace App\Actions\Product;

use App\Models\Product;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Product::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Product. Please try again.', 1);
            }
            $model->inventories()->delete();

            $return['success'] = true;
            $return['message'] = 'Successfully Update Product';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
