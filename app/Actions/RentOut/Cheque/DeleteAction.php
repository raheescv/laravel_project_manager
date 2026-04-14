<?php

namespace App\Actions\RentOut\Cheque;

use App\Models\RentOutCheque;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOutCheque::find($id);
            if (! $model) {
                throw new \Exception("RentOut Cheque not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Cheque. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Cheque';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
