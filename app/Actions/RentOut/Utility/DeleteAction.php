<?php

namespace App\Actions\RentOut\Utility;

use App\Models\RentOutUtility;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOutUtility::find($id);
            if (! $model) {
                throw new \Exception("RentOut Utility not found with the specified ID: $id.", 1);
            }
            // Delete related terms first
            $model->terms()->delete();
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
