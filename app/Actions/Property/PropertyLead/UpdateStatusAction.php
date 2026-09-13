<?php

namespace App\Actions\Property\PropertyLead;

use App\Models\PropertyLead;
use Illuminate\Validation\Rule;

/**
 * Changes only a lead's status, for a lead board drop or a "Move to" tap.
 *
 * UpdateAction re-validates the whole lead (name, source, mobile format), so a
 * legacy row with an odd mobile number could never be moved through it.
 */
class UpdateStatusAction
{
    public function execute($id, $status, $userId): array
    {
        try {
            /** @var PropertyLead|null $model */
            $model = PropertyLead::find($id);
            if (! $model) {
                throw new \Exception("Lead not found with the specified ID: $id.", 1);
            }

            validationHelper(
                ['status' => ['required', 'string', Rule::in(array_keys(leadStatuses()))]],
                ['status' => $status]
            );

            $from = $model->status;
            $model->update(['status' => $status, 'updated_by' => $userId]);

            return [
                'success' => true,
                'message' => "Moved {$model->name} to {$status}",
                'data' => ['id' => $model->id, 'from' => $from, 'model' => $model],
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}
