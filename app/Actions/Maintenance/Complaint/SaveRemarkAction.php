<?php

namespace App\Actions\Maintenance\Complaint;

use App\Models\MaintenanceComplaint;

/**
 * Persist the technician remark on a complaint. Shared by the web complaint
 * page (Complaint::save('pending')) and the mobile technician API.
 */
class SaveRemarkAction
{
    public function execute($id, $remark, $userId)
    {
        try {
            $model = MaintenanceComplaint::find($id);
            if (! $model) {
                throw new \Exception("Maintenance Complaint not found with the specified ID: $id.", 1);
            }

            $model->update([
                'technician_remark' => $remark,
                'updated_by' => $userId,
            ]);

            $return['success'] = true;
            $return['message'] = 'Saved successfully.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
