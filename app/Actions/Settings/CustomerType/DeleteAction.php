<?php

namespace App\Actions\Settings\CustomerType;

use App\Models\CustomerType;
use Exception;
use Illuminate\Support\Facades\Auth;

class DeleteAction
{
    public function execute($id)
    {
        try {
            if (! Auth::user()->can('customer type.delete')) {
                throw new Exception("You don't have permission to delete the customer type it.", 1);
            }
            $model = CustomerType::find($id);
            if (! $model) {
                throw new Exception("Customer Type not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the CustomerType. Please try again.', 1);
            }
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
