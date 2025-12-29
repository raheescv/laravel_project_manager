<?php

namespace App\Actions\Package\Payment;

use App\Models\Package;
use App\Models\PackagePayment;
use Illuminate\Support\Facades\Auth;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['created_by'] = Auth::id();
            validationHelper(PackagePayment::rules(), $data);

            $model = PackagePayment::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Package Payment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
