<?php

namespace App\Actions\Package;

use App\Models\Package;
use Exception;
use Illuminate\Support\Facades\Auth;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(Package::rules(), $data);
            $data['created_by'] = Auth::id();
            $data['paid'] = $data['paid'] ?? 0;
            $model = Package::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Package';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
