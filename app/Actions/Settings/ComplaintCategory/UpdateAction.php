<?php

namespace App\Actions\Settings\ComplaintCategory;

use App\Models\ComplaintCategory;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = ComplaintCategory::find($id);
            if (! $model) {
                throw new \Exception("Complaint Category not found with the specified ID: $id.", 1);
            }
            $data['name'] = trim($data['name']);
            validationHelper(ComplaintCategory::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Complaint Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
