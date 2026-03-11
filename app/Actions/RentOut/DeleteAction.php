<?php

namespace App\Actions\RentOut;

use App\Enums\Property\PropertyStatus;
use App\Models\Journal;
use App\Models\RentOut;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOut::find($id);
            if (! $model) {
                throw new \Exception("RentOut not found with the specified ID: $id.", 1);
            }

            // Cascade delete related records
            $model->paymentTerms()->delete();
            $model->securities()->delete();
            $model->extends()->delete();
            $model->cheques()->delete();
            $model->utilities()->each(function ($utility) {
                $utility->terms()->delete();
                $utility->delete();
            });
            $model->services()->delete();
            $model->notes()->delete();

            // Delete journal entries
            Journal::where('model', 'RentOut')
                ->where('model_id', $model->id)
                ->delete();

            // Free up the property
            $property = $model->property;
            if ($property) {
                $property->update([
                    'status' => PropertyStatus::Vacant->value,
                    'availability_status' => 'available',
                ]);
            }

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the RentOut Agreement. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted RentOut Agreement';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
