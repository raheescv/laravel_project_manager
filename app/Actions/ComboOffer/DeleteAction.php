<?php

namespace App\Actions\ComboOffer;

use App\Models\ComboOffer;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = ComboOffer::find($id);
            if (! $model) {
                throw new Exception("ComboOffer not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the ComboOffer. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted ComboOffer';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
