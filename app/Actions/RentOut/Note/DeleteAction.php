<?php

namespace App\Actions\RentOut\Note;

use App\Models\RentOutNote;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOutNote::find($id);
            if (! $model) {
                throw new \Exception("RentOut Note not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Note. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Note';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
