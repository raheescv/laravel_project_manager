<?php

namespace App\Actions\PropertyAppointment;

use App\Models\PropertyAppointment;

class RevokeLinkAction
{
    /** Expires the public link immediately without touching an existing appointment. */
    public function execute($id, $userId)
    {
        try {
            $model = PropertyAppointment::findOrFail($id);

            $model->update([
                'token_expires_at' => now()->subMinute(),
                'updated_by' => $userId,
            ]);

            $return['success'] = true;
            $return['message'] = 'Appointment link revoked';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
