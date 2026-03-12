<?php

namespace App\Actions\RentOut;

use App\Enums\Property\PropertyStatus;
use App\Models\Property;
use App\Models\RentOut;

class CreateAction
{
    public function execute($data, $userId)
    {
        try {
            $data['created_by'] = $userId;
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['tenant_id'] = $data['tenant_id'] ?? auth()->user()->tenant_id;

            validationHelper(RentOut::rules(), $data, 'RentOut');

            // Check property availability for rental agreements
            if (($data['agreement_type'] ?? 'rental') === 'rental') {
                $property = Property::find($data['property_id']);
                if ($property && $property->status === PropertyStatus::Occupied) {
                    throw new \Exception('Property is not empty');
                }
            }

            // Check overlapping
            $overlapping = RentOut::getOverlapping($data['property_id'], $data['start_date'], $data['end_date']);
            if ($overlapping->count() > 0) {
                throw new \Exception('Property has overlapping rent-out agreements');
            }

            $rentOut = RentOut::create($data);

            // Update property status
            $property = $rentOut->property;
            if ($property) {
                $property->update([
                    'status' => PropertyStatus::Occupied->value,
                    'availability_status' => 'sold',
                ]);
            }

            // Create securities if provided
            if (! empty($data['security_amount'])) {
                foreach ($data['security_amount'] as $securityData) {
                    $securityData['rent_out_id'] = $rentOut->id;
                    $securityData['tenant_id'] = $rentOut->tenant_id;
                    $securityData['branch_id'] = $rentOut->branch_id;
                    $response = (new Security\CreateAction())->execute($securityData);
                    if (! $response['success']) {
                        throw new \Exception($response['message']);
                    }
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created RentOut Agreement';
            $return['data'] = $rentOut;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
