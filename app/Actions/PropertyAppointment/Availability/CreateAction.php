<?php

namespace App\Actions\PropertyAppointment\Availability;

use App\Models\PropertyAppointmentAvailability;

class CreateAction
{
    public function execute($data, $userId)
    {
        try {
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['created_by'] = $userId;

            validationHelper(PropertyAppointmentAvailability::rules(), $data);

            $model = PropertyAppointmentAvailability::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully added availability';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
