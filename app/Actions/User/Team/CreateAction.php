<?php

namespace App\Actions\User\Team;

use App\Models\EmployeeTeam;

class CreateAction
{
    public function execute(array $data): array
    {
        try {
            validationHelper(EmployeeTeam::rules($data), $data, null, EmployeeTeam::messages());
            $model = EmployeeTeam::create($data);

            $return['success'] = true;
            $return['message'] = 'Team member successfully added.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
