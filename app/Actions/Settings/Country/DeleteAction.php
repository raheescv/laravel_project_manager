<?php

namespace App\Actions\Settings\Country;

use App\Models\Country;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Country::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Country. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update Country';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
