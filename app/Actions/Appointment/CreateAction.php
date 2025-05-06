<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;

class CreateAction
{
    public function execute($data, $userId)
    {
        try {
            $data['created_by'] = $data['updated_by'] = $userId;

            validationHelper(Appointment::rules(), $data);
            $model = Appointment::create($data);

            foreach ($data['items'] as $item) {
                $item['appointment_id'] = $model->id;
                $item['updated_by'] = $userId;
                $item['created_by'] = $userId;
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
