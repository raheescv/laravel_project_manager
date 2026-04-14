<?php

namespace App\Actions\Maintenance\Complaint;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Models\MaintenanceComplaint;

class CompleteAction
{
    public function execute($id, $userId, $remark = null)
    {
        try {
            $model = MaintenanceComplaint::find($id);
            if (! $model) {
                throw new \Exception("Maintenance Complaint not found with the specified ID: $id.", 1);
            }
            $data = [
                'completed_by' => $userId,
                'completed_at' => now(),
                'status' => MaintenanceComplaintStatus::Completed,
            ];
            if ($remark) {
                $data['technician_remark'] = $remark;
            }
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Completed Complaint';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
