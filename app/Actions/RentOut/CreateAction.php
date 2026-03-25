<?php

namespace App\Actions\RentOut;

use App\Enums\Property\PropertyStatus;
use App\Enums\RentOut\AgreementType;
use App\Helpers\Facades\RentOutTransactionHelper;
use App\Models\Property;
use App\Models\RentOut;
use Illuminate\Support\Facades\Auth;

class CreateAction
{
    public function execute($data, $userId)
    {
        try {
            $data['created_by'] = $userId;
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['tenant_id'] = $data['tenant_id'] ?? Auth::user()->tenant_id;

            $agreementType = $data['agreement_type'] ?? 'rental';

            validationHelper(RentOut::rules(), $data, 'RentOut');

            // Check property availability for rental agreements
            if ($agreementType === AgreementType::Rental) {
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
                $propertyData = ['status' => PropertyStatus::Occupied->value];
                if ($agreementType === AgreementType::Lease) {
                    $propertyData['availability_status'] = 'sold';
                }
                $property->update($propertyData);
            }

            // Handle down payment via RentOutTransaction
            if ($rentOut['down_payment'] > 0) {
                $response = RentOutTransactionHelper::storeDownPayment($rentOut, $userId);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
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
