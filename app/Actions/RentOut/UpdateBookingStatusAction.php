<?php

namespace App\Actions\RentOut;

use App\Helpers\Facades\RentOutTransactionHelper;
use App\Jobs\RentOutNotificationJob;
use App\Models\RentOut;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateBookingStatusAction
{
    public function execute($id, $status)
    {
        try {
            /** @var RentOut|null $model */
            $model = RentOut::find($id);
            if (! $model) {
                throw new Exception('RentOut not found.');
            }

            $oldStatus = $model->booking_status;
            $date = now();
            $userId = Auth::id();
            $data = ['booking_status' => $status];
            switch ($status) {
                case 'financial approved':
                    if (in_array($oldStatus, ['approved', 'completed'])) {
                        throw new Exception('Changing back to a previous status is not allowed.');
                    }
                    $data['financial_approved_at'] = $date;
                    $data['financial_approved_by'] = $userId;

                    // Handle management fee via RentOutTransaction
                    if ($model['management_fee'] > 0) {
                        $response = RentOutTransactionHelper::storeManagementFee($model, $userId);
                        if (! $response['success']) {
                            throw new Exception($response['message'], 1);
                        }
                    }
                    break;

                case 'approved':
                    if (in_array($oldStatus, ['completed'])) {
                        throw new Exception('Changing back to a previous status is not allowed.');
                    }
                    $data['approved_at'] = $date;
                    $data['approved_by'] = $userId;
                    break;

                case 'completed':
                    $data['completed_at'] = $date;
                    $data['completed_by'] = $userId;
                    break;
            }

            $model->update($data);

            $statusLabels = [
                'submitted'          => 'Submitted',
                'financial approved' => 'Financial Approved',
                'approved'           => 'Legal Approved',
                'completed'          => 'Completed',
            ];

            if (isset($statusLabels[$status])) {
                RentOutNotificationJob::dispatch(
                    title: 'RentOut Booking '.$statusLabels[$status],
                    content: 'Booking #'.$model->id.' status changed to '.$statusLabels[$status].'.',
                    link: route('property::rent::booking.view', $model->id),
                    modelId: $model->id,
                    excludedUserId: Auth::id(),
                );
            }

            return [
                'success' => true,
                'message' => 'Successfully updated status.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
