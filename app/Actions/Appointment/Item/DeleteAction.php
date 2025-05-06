<?php

namespace App\Actions\Appointment\Item;

use App\Models\AppointmentItem;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = AppointmentItem::find($id);
            if (! $model) {
                throw new Exception("Resource not found with the specified ID: $id.", 1);
            }

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the AppointmentItem. Please try again.', 1);
            }
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
