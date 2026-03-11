<?php

namespace App\Actions\RentOut\Utility\Term;

use App\Models\RentOutUtilityTerm;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(RentOutUtilityTerm::rules(), $data, 'Utility Term');
            $model = RentOutUtilityTerm::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Utility Term';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
