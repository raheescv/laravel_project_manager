<?php

namespace App\Actions\Settings\Utility;

use App\Models\Utility;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            validationHelper(Utility::rules(), $data, 'Utility');
            $exists = Utility::withTrashed()->firstWhere('name', $data['name']);
            if ($exists) {
                $model = $exists->restore();
            } else {
                $model = Utility::create($data);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Utility';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
