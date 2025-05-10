<?php

namespace App\Actions\ComboOffer;

use App\Models\ComboOffer;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(ComboOffer::rules(), $data);
            $model = ComboOffer::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created ComboOffer';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
