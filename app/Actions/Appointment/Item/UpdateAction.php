<?php

namespace App\Actions\Appointment\Item;

use App\Models\AppointmentItem;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = AppointmentItem::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            validationHelper(AppointmentItem::rules($data, $id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update AppointmentItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
