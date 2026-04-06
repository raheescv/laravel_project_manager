<?php

namespace App\Actions\Settings\ComplaintCategory;

use App\Models\ComplaintCategory;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = ComplaintCategory::find($id);
            if (! $model) {
                throw new \Exception("Complaint Category not found with the specified ID: $id.", 1);
            }
            if ($model->complaints()->exists()) {
                throw new \Exception('Cannot delete Complaint Category. There are complaints using this category.', 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Complaint Category. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Complaint Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
