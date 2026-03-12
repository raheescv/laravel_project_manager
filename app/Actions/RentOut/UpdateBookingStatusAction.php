<?php

namespace App\Actions\RentOut;

use App\Models\RentOut;
use Exception;

class UpdateBookingStatusAction
{
    public function execute($id, $status)
    {
        try {
            $model = RentOut::find($id);
            if (! $model) {
                throw new Exception('RentOut not found.');
            }

            $oldStatus = $model->booking_status;
            $date = now();
            $userId = auth()->id();
            $data = ['booking_status' => $status];

            switch ($status) {
                case 'submitted':
                    if (in_array($oldStatus, ['financial approved', 'approved', 'completed'])) {
                        throw new Exception('Changing back to a previous status is not allowed.');
                    }
                    $data['submitted_at'] = $date;
                    $data['submitted_by'] = $userId;
                    break;

                case 'financial approved':
                    if (in_array($oldStatus, ['approved', 'completed'])) {
                        throw new Exception('Changing back to a previous status is not allowed.');
                    }
                    $data['financial_approved_at'] = $date;
                    $data['financial_approved_by'] = $userId;
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
