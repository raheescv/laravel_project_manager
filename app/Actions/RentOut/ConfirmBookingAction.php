<?php

namespace App\Actions\RentOut;

use App\Enums\Property\PropertyStatus;
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

            $model->update(['status' => RentOutStatus::Occupied->value]);

            $property = $model->property;
            if ($property) {
                $property->update([
                    'status' => PropertyStatus::Occupied->value,
                    'availability_status' => 'sold',
                ]);
            }

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
