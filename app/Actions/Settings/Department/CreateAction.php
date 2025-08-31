<?php

namespace App\Actions\Settings\Department;

use App\Models\Department;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            validationHelper(Department::rules(), $data, 'Department');
            $exists = Department::withTrashed()->firstWhere('name', $data['name']);
            if ($exists) {
                $model = $exists->restore();
            } else {
                $model = Department::create($data);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Department';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
