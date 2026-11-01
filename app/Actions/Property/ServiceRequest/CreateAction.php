<?php

namespace App\Actions\Property\ServiceRequest;

use App\Enums\Property\ServiceRequestStatus;
use App\Models\PropertyServiceRequest;
use App\Models\RentOut;
use App\Models\Utility;

class CreateAction
{
    public function execute($data, $userId)
    {
        try {
            validationHelper(PropertyServiceRequest::rules(), $data);

            $rentOut = RentOut::where('property_id', $data['property_id'])->occupied()->latest('start_date')->first();

            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['rent_out_id'] = $rentOut?->id;
            $data['rent_out_service_status'] = PropertyServiceRequest::rentOutServiceStatus($rentOut, Utility::find($data['utility_id']));
            $data['status'] = ServiceRequestStatus::Requested;
            $data['requested_by'] = $data['requested_by'] ?? $userId;
            $data['requested_at'] = now();
            $data['notes'] = $data['notes'] ?? [];
            $data['created_by'] = $userId;

            $model = PropertyServiceRequest::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Service Request';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
