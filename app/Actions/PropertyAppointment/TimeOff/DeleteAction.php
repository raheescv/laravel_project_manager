<?php

namespace App\Actions\PropertyAppointment\TimeOff;

use App\Models\PropertyAppointmentTimeOff;

class DeleteAction
{
    public function execute($id, $userId)
    {
        try {
            $model = PropertyAppointmentTimeOff::findOrFail($id);
            $model->delete();

            $return['success'] = true;
            $return['message'] = 'Successfully removed time off';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
