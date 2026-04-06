<?php

namespace App\Actions\Maintenance;

use App\Models\Maintenance;
use App\Models\MaintenanceComplaint;

class CreateAction
{
    public function execute($data, $complaints = [])
    {
        try {
            validationHelper(Maintenance::rules(), $data, 'Maintenance');
            $maintenance = Maintenance::create($data);

            foreach ($complaints as $complaint) {
                $complaint['maintenance_id'] = $maintenance->id;
                $complaint['tenant_id'] = $maintenance->tenant_id;
                $complaint['branch_id'] = $maintenance->branch_id;
                $complaint['created_by'] = $maintenance->created_by;
                MaintenanceComplaint::create($complaint);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Maintenance Request';
            $return['data'] = $maintenance;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
