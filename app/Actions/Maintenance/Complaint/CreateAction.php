<?php

namespace App\Actions\Maintenance\Complaint;

use App\Models\MaintenanceComplaint;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(MaintenanceComplaint::rules(), $data, 'Maintenance Complaint');
            $model = MaintenanceComplaint::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Maintenance Complaint';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
