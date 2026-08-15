<?php

namespace App\Actions\PropertyAppointment\Availability;

use App\Models\PropertyAppointmentAvailability;

class UpdateAction
{
    public function execute($data, $id, $userId)
    {
        try {
            $model = PropertyAppointmentAvailability::findOrFail($id);
            $data['updated_by'] = $userId;

            validationHelper(PropertyAppointmentAvailability::rules($id), array_merge($model->toArray(), $data));

            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully updated availability';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
