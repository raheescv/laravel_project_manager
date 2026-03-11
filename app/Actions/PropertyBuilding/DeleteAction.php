<?php

namespace App\Actions\PropertyBuilding;

use App\Models\PropertyBuilding;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PropertyBuilding::find($id);
            if (! $model) {
                throw new \Exception("Property Building not found with the specified ID: $id.", 1);
            }
            if ($model->properties()->exists()) {
                throw new \Exception('Cannot delete Property Building. There are properties in this building.', 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Property Building. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Property Building';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
