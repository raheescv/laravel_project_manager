<?php

namespace App\Actions\User\Team;

use App\Models\EmployeeTeam;
use Exception;

class DeleteAction
{
    public function execute(int $id): array
    {
        try {
            $model = EmployeeTeam::find($id);
            if (! $model) {
                throw new Exception('Team member not found.', 1);
            }
            $model->delete();

            $return['success'] = true;
            $return['message'] = 'Team member successfully removed.';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
