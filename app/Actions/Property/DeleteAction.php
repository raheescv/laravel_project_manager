<?php

namespace App\Actions\Property;

use App\Enums\RentOut\RentOutStatus;
use App\Models\Property;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Property::find($id);
            if (! $model) {
                throw new \Exception("Property not found with the specified ID: $id.", 1);
            }
            $activeRentOuts = $model->rentOuts()
                ->whereIn('status', [RentOutStatus::Occupied, RentOutStatus::Booked])
                ->exists();
            if ($activeRentOuts) {
                throw new \Exception('Cannot delete Property. There are active rent out agreements for this property.', 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Property. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Property';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
