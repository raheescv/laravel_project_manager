<?php

namespace App\Actions\Property\PropertyLead;

use App\Models\PropertyLead;

class UpdateAction
{
    public function execute(array $data, $id, $userId): array
    {
        try {
            /** @var PropertyLead|null $model */
            $model = PropertyLead::find($id);
            if (! $model) {
                throw new \Exception("Lead not found with the specified ID: $id.", 1);
            }

            $data['name'] = trim($data['name'] ?? '');
            $data['mobile'] = isset($data['mobile']) ? trim($data['mobile']) : null;
            $data['updated_by'] = $userId;

            validationHelper(PropertyLead::rules($id, $data), $data, 'PropertyLead');

            $model->update($data);

            return [
                'success' => true,
                'message' => 'Successfully Updated Lead',
                'data' => ['id' => $model->id, 'model' => $model->fresh()],
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}
