<?php

namespace App\Actions\PropertyAppointment;

use App\Models\PropertyAppointment;

class StatusAction
{
    /** Marks a appointment completed or as a no-show after the fact. */
    public function execute($id, $status, $userId)
    {
        try {
            if (! in_array($status, ['completed', 'no_show'], true)) {
                throw new \Exception('Unsupported appointment status.', 1);
            }

            $model = PropertyAppointment::findOrFail($id);

            if ($model->status !== 'scheduled') {
                throw new \Exception('Only a confirmed appointment can be marked '.str_replace('_', '-', $status).'.', 1);
            }

            $model->update(['status' => $status, 'updated_by' => $userId]);

            $return['success'] = true;
            $return['message'] = 'Appointment marked '.str_replace('_', '-', $status);
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
