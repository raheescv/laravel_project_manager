<?php

namespace App\Actions\Settings\Complaint;

use App\Models\Complaint;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            validationHelper(Complaint::rules(), $data, 'Complaint');
            $exists = Complaint::withTrashed()
                ->where('name', $data['name'])
                ->where('complaint_category_id', $data['complaint_category_id'])
                ->first();
            if ($exists) {
                $model = $exists->restore();
            } else {
                $model = Complaint::create($data);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Complaint';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
