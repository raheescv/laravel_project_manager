<?php

namespace App\Actions\RentOut;

use App\Enums\Property\PropertyStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;

class VacateAction
{
    public function execute($id, $vacateDate = null)
    {
        try {
            $model = RentOut::find($id);
            if (! $model) {
                throw new \Exception("RentOut not found with the specified ID: $id.");
            }

            $model->update([
                'status' => RentOutStatus::Vacated->value,
                'vacate_date' => $vacateDate ?? now()->toDateString(),
            ]);

            // Free up the property
            $property = $model->property;
            if ($property) {
                $property->update([
                    'status' => PropertyStatus::Vacant->value,
                    'availability_status' => 'available',
                ]);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Vacated RentOut Agreement';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
