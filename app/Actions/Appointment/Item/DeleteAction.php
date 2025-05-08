<?php

namespace App\Actions\Appointment\Item;

use App\Models\AppointmentItem;
use Exception;
use Illuminate\Support\Facades\Auth;

class DeleteAction
{
    public function execute($id)
    {
        try {
            if (Auth::user()->cannot('appointment.delete')) {
                throw new \Exception('You do not have permission to delete this appointment item.', 1);
            }
            $model = AppointmentItem::find($id);
            if (! $model) {
                throw new Exception("AppointmentItem not found with the specified ID: $id.", 1);
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
