<?php

namespace App\Actions\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\RentOutBookingStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use Illuminate\Support\Facades\Auth;

class ConfirmBookingAction
{
    /**
     * Confirm a booking by converting it to an active agreement.
     *
     * Steps:
     * 2. Set status to Occupied
     * 3. Update the property status (occupied for rental, sold for lease)
     * 4. Create management fee journal entries if applicable
     * 5. Create down payment journal entries if applicable
     */
    public function execute($id): array
    {
        try {
            $model = RentOut::where('status', RentOutStatus::Booked)->find($id);

            if (! $model) {
                throw new \Exception("Booked RentOut not found with the specified ID: $id.");
            }
            // 1. Set status to Occupied
            $model->update([
                'booking_status' => RentOutBookingStatus::Submitted->value,
            ]);

            // // 2. Update property status
            // if ($model->property) {
            //     $propertyData = ['status' => PropertyStatus::Occupied->value];

            //     // For lease/sale agreements, also mark as sold
            //     if ($model->agreement_type === AgreementType::Lease) {
            //         $propertyData['availability_status'] = PropertyStatus::Sold->value;
            //     }

            //     $model->property->update($propertyData);
            // }

            // // 4. Create management fee journal entries if applicable
            // if ($model->management_fee > 0) {
            //     (new JournalEntryAction)->executeManagementFee($model,Auth::id());
            // }

            // // 5. Create down payment journal entries if applicable
            // if (($model->down_payment ?? 0) > 0) {
            //     (new JournalEntryAction)->executeDownPayment($model,Auth::id());
            // }

            $return['success'] = true;
            $return['message'] = 'Booking confirmed successfully.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
