<?php

namespace App\Actions\Property\PropertyLead;

use App\Models\PropertyLead;

class DeleteAction
{
    public function execute($id): array
    {
        try {
            /** @var PropertyLead|null $model */
            $model = PropertyLead::find($id);
            if (! $model) {
                throw new \Exception("Lead not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Lead. Please try again.', 1);
            }

            return [
                'success' => true,
                'message' => 'Successfully Deleted Lead',
                'data' => $model,
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}
