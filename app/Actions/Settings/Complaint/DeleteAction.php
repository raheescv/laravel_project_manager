<?php

namespace App\Actions\Settings\Complaint;

use App\Models\Complaint;
use App\Models\MaintenanceComplaint;

class DeleteAction
{
    public function execute($id)
    {
        try {
            /** @var Complaint|null $model */
            $model = Complaint::find($id);
            if (! $model) {
                throw new \Exception("Complaint not found with the specified ID: $id.", 1);
            }
            if (MaintenanceComplaint::where('complaint_id', $model->id)->exists()) {
                throw new \Exception('Cannot delete Complaint. It is being used in maintenance requests.', 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Complaint. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Complaint';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
