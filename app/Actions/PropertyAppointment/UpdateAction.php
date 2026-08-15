<?php

namespace App\Actions\PropertyAppointment;

use App\Models\PropertyAppointment;

class UpdateAction
{
    public $model;

    public $userId;

    public function execute($data, $id, $userId)
    {
        $this->userId = $userId;
        try {
            $this->model = PropertyAppointment::findOrFail($id);

            $data['updated_by'] = $this->userId;

            validationHelper(PropertyAppointment::rules($id), array_merge($this->model->toArray(), $data));

            $this->model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully updated appointment';
            $return['data'] = $this->model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
