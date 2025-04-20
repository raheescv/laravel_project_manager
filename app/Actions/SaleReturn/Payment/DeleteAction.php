<?php

namespace App\Actions\SaleReturn\Payment;

use App\Models\SaleReturnPayment;
use Exception;
use Illuminate\Support\Facades\Auth;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = SaleReturnPayment::find($id);
            if (! $model) {
                throw new Exception("Resource not found with the specified ID: $id.", 1);
            }
            if ($model->saleReturn->status == 'completed') {
                if (! Auth::user()->can('sales return.edit completed')) {
                    throw new Exception("You don't have permission to delete it.", 1);
                }
                dd('journal delete');
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the SaleReturnPayment. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update SaleReturnPayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
