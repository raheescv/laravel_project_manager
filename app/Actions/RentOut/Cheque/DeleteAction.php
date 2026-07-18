<?php

namespace App\Actions\RentOut\Cheque;

use App\Actions\RentOut\Payment\ReverseTransactionAction;
use App\Models\RentOutCheque;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOutCheque::find($id);
            if (! $model) {
                throw new \Exception("RentOut Cheque not found with the specified ID: $id.", 1);
            }

            DB::transaction(function () use ($model) {
                // Roll back any clearance receipt this cheque produced: its ledger
                // rows, journal + entries, and the payment term it marked paid.
                (new ReverseTransactionAction())->reverseForCheque($model);

                if (! $model->delete()) {
                    throw new \Exception('Oops! Something went wrong while deleting the Cheque. Please try again.', 1);
                }
            });

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Cheque';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
