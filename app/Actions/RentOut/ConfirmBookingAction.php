<?php

namespace App\Actions\RentOut;

use App\Enums\RentOut\RentOutBookingStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;

class ConfirmBookingAction
{
    public function execute($id)
    {
        try {
            $model = RentOut::where('status', RentOutStatus::Booked)->find($id);
            if (! $model) {
                throw new \Exception("Booked RentOut not found with the specified ID: $id.");
            }

            $model->update([
                'booking_status' => RentOutBookingStatus::Submitted->value,
            ]);

            $return['success'] = true;
            $return['message'] = 'Booking confirmed successfully';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
