<?php

namespace App\Actions\Settings\Unit;

use App\Models\Unit;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Unit::find($id);
            if (! $model) {
                throw new \Exception("Unit not found with the specified ID: $id.", 1);
            }

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Unit. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update Unit';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
