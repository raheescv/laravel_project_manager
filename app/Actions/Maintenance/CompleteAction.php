<?php

namespace App\Actions\Maintenance;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Models\Maintenance;

class CompleteAction
{
    public function execute($id, $userId)
    {
        try {
            $model = Maintenance::find($id);
            if (! $model) {
                throw new \Exception("Maintenance not found with the specified ID: $id.", 1);
            }
            $pendingComplaints = $model->maintenanceComplaints()
                ->whereNotIn('status', [MaintenanceComplaintStatus::Completed->value, MaintenanceComplaintStatus::Cancelled->value])
                ->count();
            if ($pendingComplaints > 0) {
                throw new \Exception('Cannot complete Maintenance. There are still pending or assigned complaints.', 1);
            }
            $model->update([
                'status' => MaintenanceStatus::Completed,
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);
            $return['success'] = true;
            $return['message'] = 'Successfully Completed Maintenance Request';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
