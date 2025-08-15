<?php

namespace App\Actions\Purchase\Payment;

use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use Exception;
use Illuminate\Support\Facades\Auth;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PurchasePayment::find($id);
            if (! $model) {
                throw new Exception("PurchasePayment not found with the specified ID: $id.", 1);
            }
            $purchase = $model->purchase;
            if ($purchase->status == 'completed') {
                if (! Auth::user()->can('purchase.delete payment after completed')) {
                    throw new Exception("You don't have permission to delete it.", 1);
                }
                JournalEntry::where('model', 'PurchasePayment')->where('model_id', $model->id)->delete();
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the PurchasePayment. Please try again.', 1);
            }

            Purchase::updatePurchasePaymentMethods($purchase);

            $return['success'] = true;
            $return['message'] = 'Successfully Update PurchasePayment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
