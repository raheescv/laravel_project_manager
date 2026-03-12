<?php

namespace App\Actions\Settings\Utility;

use App\Models\Utility;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Utility::find($id);
            if (! $model) {
                throw new \Exception("Utility not found with the specified ID: $id.", 1);
            }
            if ($model->utilityTerms()->exists()) {
                throw new \Exception('Cannot delete Utility. There are utility terms using this utility.', 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Utility. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Utility';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
