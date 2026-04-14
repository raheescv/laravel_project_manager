<?php

namespace App\Actions\Maintenance\Complaint;

use App\Models\MaintenanceComplaint;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = MaintenanceComplaint::find($id);
            if (! $model) {
                throw new \Exception("Maintenance Complaint not found with the specified ID: $id.", 1);
            }
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Maintenance Complaint';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
