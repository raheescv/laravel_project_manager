<?php

namespace App\Actions\RentOut\Cheque;

use App\Models\RentOutCheque;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(RentOutCheque::rules(), $data, 'RentOut Cheque');
            $model = RentOutCheque::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Cheque';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
