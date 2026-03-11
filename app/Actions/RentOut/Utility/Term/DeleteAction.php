<?php

namespace App\Actions\RentOut\Utility\Term;

use App\Models\RentOutUtilityTerm;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOutUtilityTerm::find($id);
            if (! $model) {
                throw new \Exception("Utility Term not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Utility Term. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Utility Term';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
