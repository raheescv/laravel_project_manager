<?php

namespace App\Actions\Settings\CustomerType;

use App\Models\CustomerType;
use Exception;
use Illuminate\Support\Facades\Auth;

class CreateAction
{
    public function execute($data)
    {
        try {
            if (! Auth::user()->can('customer type.create')) {
                throw new Exception("You don't have permission to create the customer type it.", 1);
            }
            $data['name'] = ucfirst(trim($data['name']));
            validationHelper(CustomerType::rules(), $data);
            $model = CustomerType::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created CustomerType';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
