<?php

namespace App\Actions\Settings\PropertyType;

use App\Models\PropertyType;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PropertyType::find($id);
            if (! $model) {
                throw new \Exception("Property Type not found with the specified ID: $id.", 1);
            }
            if ($model->properties()->exists()) {
                throw new \Exception('Cannot delete Property Type. There are properties using this type.', 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Property Type. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Property Type';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
