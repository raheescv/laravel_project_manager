<?php

namespace App\Actions\PropertyAppointment\Availability;

use App\Models\PropertyAppointmentAvailability;

class DeleteAction
{
    public function execute($id, $userId)
    {
        try {
            $model = PropertyAppointmentAvailability::findOrFail($id);
            $model->updated_by = $userId;
            $model->save();
            $model->delete();

            $return['success'] = true;
            $return['message'] = 'Successfully removed availability';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
