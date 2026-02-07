<?php

namespace App\Actions\Settings\Rack;

use App\Models\Rack;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(Rack::rules(), $data);
            $model = Rack::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Rack';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
