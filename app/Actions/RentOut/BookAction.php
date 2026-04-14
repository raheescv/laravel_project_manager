<?php

namespace App\Actions\RentOut;

use App\Enums\RentOut\RentOutBookingStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Helpers\Facades\RentOutTransactionHelper;
use App\Jobs\RentOutNotificationJob;
use App\Models\RentOut;

class BookAction
{
    public function execute($data, $userId)
    {
        try {
            $data['created_by'] = $userId;
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['status'] = RentOutStatus::Booked->value;
            $data['booking_status'] = RentOutBookingStatus::Created->value;

            validationHelper(RentOut::$bookingRules, $data, 'RentOut Booking');

            $rentOut = RentOut::create($data);

            // Handle management fee via RentOutTransaction
            if (! empty($data['management_fee']) && $data['management_fee'] > 0) {
                $response = RentOutTransactionHelper::storeManagementFee($rentOut, $userId);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            // Handle down payment via RentOutTransaction
            if (! empty($data['down_payment']) && $data['down_payment'] > 0) {
                $response = RentOutTransactionHelper::storeDownPayment($rentOut, $userId);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            RentOutNotificationJob::dispatch(
                title: 'New RentOut Booking Created',
                content: 'Booking #'.$rentOut->id.' has been created.',
                link: route('property::rent::booking.view', $rentOut->id),
                modelId: $rentOut->id,
                excludedUserId: $userId,
            );

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
