<?php

namespace App\Actions\Settings\CustomerType;

use App\Models\CustomerType;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            if (! Auth::user()->can('customer type.edit')) {
                throw new Exception("You don't have permission to edit the customer type it.", 1);
            }
            $data['name'] = ucfirst(trim($data['name']));
            $model = CustomerType::find($id);
            if (! $model) {
                throw new Exception("Customer Type not found with the specified ID: $id.", 1);
            }
            validationHelper(CustomerType::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Update CustomerType';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
