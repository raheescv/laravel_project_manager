<?php

namespace App\Actions\Package\Payment;

use App\Models\PackagePayment;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PackagePayment::find($id);
            if (! $model) {
                throw new Exception("Package Payment not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Package Payment. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Package Payment';
            $return['data'] = [];
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
