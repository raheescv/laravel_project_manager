<?php

namespace App\Actions\RentOut\Note;

use App\Models\RentOutNote;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(RentOutNote::rules(), $data, 'RentOut Note');
            $model = RentOutNote::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Note';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
