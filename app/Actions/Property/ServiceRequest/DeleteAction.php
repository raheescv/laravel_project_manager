<?php

namespace App\Actions\Property\ServiceRequest;

use App\Enums\Property\ServiceRequestStatus;
use App\Models\PropertyServiceRequest;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PropertyServiceRequest::find($id);
            if (! $model) {
                throw new \Exception("Service Request not found with the specified ID: $id.", 1);
            }
            if ($model->status === ServiceRequestStatus::Completed) {
                throw new \Exception("Service Request #{$id} is completed and cannot be deleted.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Service Request. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Service Request';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
