<?php

namespace App\Actions\PurchaseReturn\Payment;

use App\Models\PurchaseReturnPayment;
use Exception;
use Illuminate\Support\Facades\Auth;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PurchaseReturnPayment::find($id);
            if (! $model) {
                throw new Exception("PurchaseReturnPayment not found with the specified ID: $id.", 1);
            }
            if ($model->purchaseReturn->status == 'completed') {
                if (! Auth::user()->can('purchase return.edit completed')) {
                    throw new Exception("You don't have permission to delete it.", 1);
                }
                dd('journal delete');
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the PurchaseReturnPayment. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update PurchaseReturnPayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
