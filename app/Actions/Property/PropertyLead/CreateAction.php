<?php

namespace App\Actions\Property\PropertyLead;

use App\Models\PropertyLead;

class CreateAction
{
    public function execute(array $data, $userId): array
    {
        try {
            $data['name'] = trim($data['name'] ?? '');
            $data['mobile'] = isset($data['mobile']) ? trim($data['mobile']) : null;
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;

            validationHelper(PropertyLead::rules(0, $data), $data, 'PropertyLead');

            $model = PropertyLead::create($data);

            return [
                'success' => true,
                'message' => 'Successfully Created Lead',
                'data' => ['id' => $model->id, 'model' => $model],
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}
