<?php

namespace App\Actions\Maintenance\Complaint;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Models\MaintenanceComplaint;

class AssignAction
{
    public function execute($id, $technicianId, $userId)
    {
        try {
            $model = MaintenanceComplaint::find($id);
            if (! $model) {
                throw new \Exception("Maintenance Complaint not found with the specified ID: $id.", 1);
            }
            $model->update([
                'technician_id' => $technicianId,
                'assigned_by' => $userId,
                'assigned_at' => now(),
                'status' => MaintenanceComplaintStatus::Assigned,
            ]);
            $return['success'] = true;
            $return['message'] = 'Successfully Assigned Technician';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
