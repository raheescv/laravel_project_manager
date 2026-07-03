<?php

namespace App\Actions\Maintenance\Complaint;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Models\MaintenanceComplaint;
use Illuminate\Support\Facades\DB;

class CompleteAction
{
    /**
     * Mark a complaint completed and, once no sibling on the same maintenance
     * remains open, auto-complete the parent maintenance too (Complaint.php
     * lines ~525-538). Shared by the web complaint page, the assign screen and
     * the mobile technician API.
     */
    public function execute($id, $userId, $remark = null)
    {
        try {
            $model = MaintenanceComplaint::find($id);
            if (! $model) {
                throw new \Exception("Maintenance Complaint not found with the specified ID: $id.", 1);
            }

            DB::transaction(function () use ($model, $userId, $remark) {
                $data = [
                    'completed_by' => $userId,
                    'completed_at' => now(),
                    'status' => MaintenanceComplaintStatus::Completed,
                    'updated_by' => $userId,
                ];
                if ($remark) {
                    $data['technician_remark'] = $remark;
                }
                $model->update($data);

                // Auto-complete the parent maintenance when every complaint on it
                // is completed or cancelled.
                $maintenance = $model->maintenance;
                if ($maintenance) {
                    $allDone = $maintenance->maintenanceComplaints()
                        ->whereNotIn('status', ['completed', 'cancelled'])
                        ->count() === 0;

                    if ($allDone) {
                        $maintenance->update([
                            'status' => 'completed',
                            'completed_by' => $userId,
                            'completed_at' => now(),
                        ]);
                    }
                }
            });

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
