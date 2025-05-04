<?php

namespace App\Actions\Settings\Country;

use App\Models\Country;

class CreateAction
{
    public $data;

    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            $data['code'] = trim($data['code']);
            $this->data = $data;
            validationHelper(Country::rules(), $this->data);
            $model = Country::create($this->data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Country';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
