<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public function execute($data, $id, $userId)
    {
        try {
            if (Auth::user()->cannot('appointment.edit')) {
                throw new \Exception('You do not have permission to update this appointment.', 1);
            }
            $return = [];
            $model = Appointment::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            $data['updated_by'] = $userId;
            validationHelper(Appointment::rules($id), $data);
            $model->update($data);

            foreach ($data['items'] as $item) {
                $item['appointment_id'] = $model->id;
                $item['updated_by'] = $userId;
                if (isset($item['id'])) {
                    $response = (new Item\UpdateAction())->execute($item, $item['id'], $userId);
                } else {
                    $item['created_by'] = $userId;
                    $response = (new Item\CreateAction())->execute($item, $userId);
                }
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update Appointment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
