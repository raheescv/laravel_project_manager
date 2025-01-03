<?php

namespace App\Actions\Settings\Branch;

use App\Models\Branch;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Branch::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Branch. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update Branch';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
