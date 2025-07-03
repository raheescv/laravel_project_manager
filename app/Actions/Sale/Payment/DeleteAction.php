<?php

namespace App\Actions\Sale\Payment;

use App\Models\JournalEntry;
use App\Models\Sale;
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
                throw new Exception("Sale Payment not found with the specified ID: $id.", 1);
            }
            $sale = $model->sale;
            if ($sale->status == 'completed') {
                if (! Auth::user()->can('sale.edit completed')) {
                    throw new Exception("You don't have permission to delete it.", 1);
                }
                JournalEntry::where('model', 'SalePayment')->where('model_id', $model->id)->delete();
            }

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the SalePayment. Please try again.', 1);
            }
            // Update sale payment methods
            Sale::updateSalePaymentMethods($sale);

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
