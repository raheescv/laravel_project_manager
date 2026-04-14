<?php

namespace App\Actions\Settings\Complaint;

use App\Models\Complaint;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Complaint::find($id);
            if (! $model) {
                throw new \Exception("Complaint not found with the specified ID: $id.", 1);
            }
            $data['name'] = trim($data['name']);
            validationHelper(Complaint::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Complaint';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
