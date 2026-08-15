<?php

namespace App\Actions\PropertyAppointment\TimeOff;

use App\Models\PropertyAppointmentTimeOff;

class CreateAction
{
    public function execute($data, $userId)
    {
        try {
            $data['created_by'] = $userId;

            validationHelper(PropertyAppointmentTimeOff::rules(), $data);

            $model = PropertyAppointmentTimeOff::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully added time off';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
