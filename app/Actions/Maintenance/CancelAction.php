<?php

namespace App\Actions\Maintenance;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Enums\Maintenance\MaintenanceStatus;
use App\Models\Maintenance;

class CancelAction
{
    /**
     * Cancel a pending maintenance request, along with every complaint on it
     * that is not already completed.
     *
     * @return array{success: bool, message: string, data?: Maintenance}
     */
    public function execute(int|string $id, ?int $userId = null): array
    {
        try {
            $model = Maintenance::find($id);
            if (! $model) {
                throw new \Exception("Maintenance not found with the specified ID: $id.", 1);
            }
            if ($model->status !== MaintenanceStatus::Pending) {
                throw new \Exception('Only a pending maintenance request can be cancelled.', 1);
            }

            $model->update(['status' => MaintenanceStatus::Cancelled->value, 'updated_by' => $userId]);
            $model->maintenanceComplaints()
                ->where('status', '!=', MaintenanceComplaintStatus::Completed->value)
                ->update(['status' => MaintenanceComplaintStatus::Cancelled->value]);

            $return['success'] = true;
            $return['message'] = 'Successfully Cancelled Maintenance Request';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
