<?php

namespace App\Actions\Property\ServiceRequest;

use App\Enums\Property\ServiceRequestStatus;
use App\Models\PropertyServiceRequest;

class UpdateAction
{
    public function execute($data, $id, $userId)
    {
        try {
            $model = PropertyServiceRequest::find($id);
            if (! $model) {
                throw new \Exception("Service Request not found with the specified ID: $id.", 1);
            }
            if ($model->status === ServiceRequestStatus::Completed) {
                throw new \Exception('A completed Service Request can no longer be edited.', 1);
            }
            validationHelper(PropertyServiceRequest::rules($id), $data);

            $data['updated_by'] = $userId;
            unset($data['status'], $data['requested_by'], $data['requested_at'], $data['created_by']);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Service Request';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
