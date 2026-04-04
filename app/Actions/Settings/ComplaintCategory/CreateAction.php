<?php

namespace App\Actions\Settings\ComplaintCategory;

use App\Models\ComplaintCategory;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            validationHelper(ComplaintCategory::rules(), $data, 'Complaint Category');
            $exists = ComplaintCategory::withTrashed()->firstWhere('name', $data['name']);
            if ($exists) {
                $model = $exists->restore();
            } else {
                $model = ComplaintCategory::create($data);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Complaint Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
