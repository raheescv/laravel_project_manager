<?php

namespace App\Actions\Maintenance;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Models\Maintenance;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Maintenance::find($id);
            if (! $model) {
                throw new \Exception("Maintenance not found with the specified ID: $id.", 1);
            }
            $activeComplaints = $model->maintenanceComplaints()
                ->whereNotIn('status', [MaintenanceComplaintStatus::Cancelled->value, MaintenanceComplaintStatus::Completed->value])
                ->count();
            if ($activeComplaints > 0) {
                throw new \Exception('Cannot delete Maintenance. There are active complaints that need to be resolved first.', 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Maintenance Request. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Maintenance Request';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
