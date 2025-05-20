<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;

class CreateAction
{
    public function execute($data, $userId)
    {
        try {
            $data['created_by'] = $data['created_by'] ?? $userId;
            $data['updated_by'] = $data['created_by'] ?? $userId;

            validationHelper(Appointment::rules(), $data);
            $model = Appointment::create($data);

            foreach ($data['items'] as $item) {
                $item['appointment_id'] = $model->id;
                $item['created_by'] = $item['created_by'] ?? $userId;
                $item['updated_by'] = $item['updated_by'] ?? $userId;
                $response = (new Item\CreateAction())->execute($item);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Appointment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
