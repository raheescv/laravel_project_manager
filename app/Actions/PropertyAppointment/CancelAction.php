<?php

namespace App\Actions\PropertyAppointment;

use App\Models\PropertyAppointment;

class CancelAction
{
    /**
     * Cancelling frees the slot: active_slot_key becomes NULL, so the time
     * immediately becomes bookable again for this salesman.
     */
    public function execute($id, $userId, $reason = null)
    {
        try {
            $model = PropertyAppointment::findOrFail($id);

            if ($model->status === 'cancelled') {
                throw new \Exception('This appointment is already cancelled.', 1);
            }

            // Only a CONFIRMED appointment is worth apologising for. One that was
            // never booked has no time to cancel, and the customer would be told
            // about the loss of something they never had.
            $wasConfirmed = $model->status === 'scheduled';

            $model->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancel_reason' => $reason,
                'updated_by' => $userId,
            ]);

            if ($wasConfirmed) {
                // Never allowed to fail the cancellation — see NotifyAction.
                (new NotifyAction())->execute($model, 'appointment_cancelled', $userId);
            }

            $return['success'] = true;
            $return['message'] = 'Appointment cancelled';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
