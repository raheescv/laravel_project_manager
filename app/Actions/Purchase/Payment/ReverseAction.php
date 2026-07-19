<?php

namespace App\Actions\Purchase\Payment;

use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Reverses a single purchase payment created by {@see \App\Actions\Purchase\PaymentAction}.
 *
 * Recording a payment does two things: it inserts a PurchasePayment row and posts
 * the two balanced journal-entry legs (payment account vs vendor) that drive the
 * vendor statement. Reversing therefore has to roll BOTH back — unlike the plain
 * delete flow, which only strips the journals when the purchase is "completed" and
 * so leaves LPO-purchase (accepted) ledgers inflated. This action always removes
 * the payment's journal legs, drops any now-empty parent journal header, soft
 * deletes the payment, and re-syncs the purchase paid/balance totals.
 */
class ReverseAction
{
    public function execute($id)
    {
        try {
            $model = PurchasePayment::find($id);
            if (! $model) {
                throw new Exception("PurchasePayment not found with the specified ID: $id.", 1);
            }

            $purchase = $model->purchase;
            if ($purchase && $purchase->status == 'completed' && ! Auth::user()?->can('purchase.delete payment after completed')) {
                throw new Exception("You don't have permission to reverse it.", 1);
            }

            $userId = Auth::id();

            // Roll back the payment's journal legs (always — regardless of purchase status).
            $paymentEntries = JournalEntry::where('model', 'PurchasePayment')
                ->where('model_id', $model->id);
            $journalIds = (clone $paymentEntries)->pluck('journal_id')->filter()->unique();

            (clone $paymentEntries)->update(['deleted_by' => $userId]);
            (clone $paymentEntries)->delete();

            // Drop any parent journal header left with no remaining entries.
            foreach ($journalIds as $journalId) {
                if (JournalEntry::where('journal_id', $journalId)->count() === 0) {
                    Journal::where('id', $journalId)->delete();
                }
            }

            $model->deleted_by = $userId;
            $model->save();
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while reversing the payment. Please try again.', 1);
            }

            if ($purchase) {
                Purchase::updatePurchasePaymentMethods($purchase);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully reversed the purchase payment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
