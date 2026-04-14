<?php

namespace App\Actions\PropertyGroup;

use App\Models\PropertyGroup;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PropertyGroup::find($id);
            if (! $model) {
                throw new \Exception("Property Group not found with the specified ID: $id.", 1);
            }
            if ($model->buildings()->exists()) {
                throw new \Exception('Cannot delete Property Group. There are buildings in this group.', 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Property Group. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Property Group';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
