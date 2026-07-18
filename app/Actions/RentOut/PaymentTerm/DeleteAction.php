<?php

namespace App\Actions\RentOut\PaymentTerm;

use App\Actions\RentOut\Payment\ReverseTransactionAction;
use App\Models\RentOutPaymentTerm;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOutPaymentTerm::find($id);
            if (! $model) {
                throw new \Exception("Payment Term not found with the specified ID: $id.", 1);
            }

            DB::transaction(function () use ($model) {
                // Roll back any receipts recorded against this term (direct or via
                // a cheque clearance): their ledger rows, journals + entries, and
                // reset the clearing cheque back to uncleared.
                (new ReverseTransactionAction())->reverseForTerm($model);

                if (! $model->delete()) {
                    throw new \Exception('Oops! Something went wrong while deleting the Payment Term. Please try again.', 1);
                }
            });

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Payment Term';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
