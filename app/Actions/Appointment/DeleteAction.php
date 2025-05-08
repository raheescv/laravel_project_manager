<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Appointment::find($id);
            if (! $model) {
                throw new \Exception("Appointment not found with the specified ID: $id.", 1);
            }
            $model->items()->delete();
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Appointment. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update Appointment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
