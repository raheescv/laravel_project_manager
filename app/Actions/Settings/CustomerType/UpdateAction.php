<?php

namespace App\Actions\Settings\CustomerType;

use App\Models\CustomerType;

class UpdateAction
{
    public $data;

    public function execute($data, $id)
    {
        try {
            $data['name'] = ucfirst(trim($data['name']));
            $this->data = $data;
            $model = CustomerType::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            validationHelper(CustomerType::rules($id), $this->data);
            $model->update($this->data);
            $return['success'] = true;
            $return['message'] = 'Successfully Update CustomerType';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
