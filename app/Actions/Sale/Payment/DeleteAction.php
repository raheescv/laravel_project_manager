<?php

namespace App\Actions\Sale\Payment;

use App\Models\SalePayment;
use Exception;
use Illuminate\Support\Facades\Auth;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = SalePayment::find($id);
            if (! $model) {
                throw new Exception("Resource not found with the specified ID: $id.", 1);
            }
            if ($model->sale->status == 'completed') {
                if (! Auth::user()->can('sale.edit completed')) {
                    throw new Exception("You don't have permission to delete it.", 1);
                }
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the SalePayment. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update SalePayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
