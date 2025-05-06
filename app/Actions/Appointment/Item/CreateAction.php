<?php

namespace App\Actions\Appointment\Item;

use App\Models\AppointmentItem;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(AppointmentItem::rules($data), $data);

            $model = AppointmentItem::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created AppointmentItem';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
