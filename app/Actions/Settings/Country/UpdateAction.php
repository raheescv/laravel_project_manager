<?php

namespace App\Actions\Settings\Country;

use App\Models\Country;

class UpdateAction
{
    public $data;

    public function execute($data, $id)
    {
        try {
            $data['name'] = trim($data['name']);
            $data['code'] = trim($data['code']);
            $this->data = $data;
            $model = Country::find($id);
            if (! $model) {
                throw new \Exception("Country not found with the specified ID: $id.", 1);
            }
            validationHelper(Country::rules($id), $this->data);
            $model->update($this->data);
            $return['success'] = true;
            $return['message'] = 'Successfully Update Country';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
