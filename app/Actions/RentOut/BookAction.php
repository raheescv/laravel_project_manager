<?php

namespace App\Actions\RentOut;

use App\Enums\Property\PropertyStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;

class BookAction
{
    public function execute($data, $userId)
    {
        try {
            $data['created_by'] = $userId;
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['tenant_id'] = $data['tenant_id'] ?? auth()->user()->tenant_id;
            $data['status'] = RentOutStatus::Booked->value;

            validationHelper(RentOut::$bookingRules, $data, 'RentOut Booking');

            $rentOut = RentOut::create($data);

            // Update property status to booked
            $property = $rentOut->property;
            if ($property) {
                $property->update(['status' => PropertyStatus::Booked->value]);
            }

            // Handle management fee journal entry
            if (! empty($data['management_fee']) && $data['management_fee'] > 0) {
                (new JournalEntryAction())->executeManagementFee($rentOut, $userId);
            }

            // Handle down payment
            if (! empty($data['down_payment']) && $data['down_payment'] > 0) {
                (new JournalEntryAction())->executeDownPayment($rentOut, $userId);
            }

            // Handle utilities
            if (! empty($data['utilities'])) {
                foreach ($data['utilities'] as $utilityId => $checked) {
                    if ($checked) {
                        (new Utility\CreateAction())->execute([
                            'rent_out_id' => $rentOut->id,
                            'utility_id' => $utilityId,
                            'tenant_id' => $rentOut->tenant_id,
                            'branch_id' => $rentOut->branch_id,
                        ]);
                    }
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Booking';
            $return['data'] = $rentOut;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
