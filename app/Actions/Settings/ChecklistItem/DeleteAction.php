<?php

namespace App\Actions\Settings\ChecklistItem;

use App\Models\Checklist;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Checklist::find($id);
            if (! $model) {
                throw new \Exception("Checklist Item not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Checklist Item. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Checklist Item';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
