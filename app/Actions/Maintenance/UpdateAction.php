<?php

namespace App\Actions\Maintenance;

use App\Models\Maintenance;
use App\Models\MaintenanceComplaint;

class UpdateAction
{
    public function execute($data, $id, $complaints = [])
    {
        try {
            $model = Maintenance::find($id);
            if (! $model) {
                throw new \Exception("Maintenance not found with the specified ID: $id.", 1);
            }
            validationHelper(Maintenance::rules($id), $data);
            $model->update($data);

            // Sync complaints: remove old ones not in the new list, add new ones
            $existingIds = collect($complaints)->pluck('id')->filter()->toArray();
            $model->maintenanceComplaints()->whereNotIn('id', $existingIds)->delete();

            foreach ($complaints as $complaint) {
                if (! empty($complaint['id'])) {
                    $existing = MaintenanceComplaint::find($complaint['id']);
                    if ($existing) {
                        $existing->update($complaint);
                    }
                } else {
                    $complaint['maintenance_id'] = $model->id;
                    $complaint['tenant_id'] = $model->tenant_id;
                    $complaint['branch_id'] = $model->branch_id;
                    $complaint['created_by'] = $model->updated_by ?? $model->created_by;
                    MaintenanceComplaint::create($complaint);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Maintenance Request';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
