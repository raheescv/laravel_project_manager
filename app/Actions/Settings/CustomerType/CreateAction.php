<?php

namespace App\Actions\Settings\CustomerType;

use App\Models\CustomerType;

class CreateAction
{
    public $data;

    public function execute($data)
    {
        try {
            $data['name'] = ucfirst(trim($data['name']));
            $this->data = $data;
            validationHelper(CustomerType::rules(), $this->data);
            $model = CustomerType::create($this->data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created CustomerType';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
