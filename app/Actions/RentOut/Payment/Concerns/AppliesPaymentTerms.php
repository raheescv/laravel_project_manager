<?php

namespace App\Actions\RentOut\Payment\Concerns;

use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;

/**
 * The payment-term side of a receipt.
 *
 * A receipt does not just sit in the ledger — it moves a payment term's paid
 * total, and every action that undoes or re-homes a receipt has to move that
 * total back. Reversal and transfer used to carry their own copies of both the
 * term lookup and the arithmetic; they share these instead.
 */
trait AppliesPaymentTerms
{
    /**
     * The payment term a receipt was applied to, whether it is linked by model
     * (a direct term receipt) or by source (a cheque clearance).
     */
    protected function termFor(?RentOutTransaction $payment): ?RentOutPaymentTerm
    {
        if (! $payment) {
            return null;
        }

        if ($payment->model === 'RentOutPaymentTerm' && $payment->model_id) {
            return RentOutPaymentTerm::find($payment->model_id);
        }

        if ($payment->source === 'PaymentTerm' && $payment->source_id) {
            return RentOutPaymentTerm::find($payment->source_id);
        }

        return null;
    }

    /**
     * Take an amount back off a term's paid total, re-opening its balance.
     */
    protected function releaseTerm(?RentOutPaymentTerm $term, float $amount): void
    {
        if (! $term) {
            return;
        }

        $term->paid = max(0, (float) $term->paid - $amount);
        if ($term->paid <= 0) {
            $term->paid_date = null;
        }
        // The model's saving hook only flips status TO paid; force it back to
        // pending when the term is no longer fully covered.
        if ($term->paid < (float) $term->total) {
            $term->status = 'pending';
        }
        $term->save();
    }

    /**
     * Credit an amount to a term's paid total. The model's saving hook flips
     * the status to paid once the term is fully covered.
     *
     * A new payment stamps its own date; restoring a payment that was moved
     * away keeps whatever date the term already carries, since the original
     * receipt is what is being put back.
     */
    protected function creditTerm(?RentOutPaymentTerm $term, float $amount, string $date, bool $stampPaidDate = true): void
    {
        if (! $term) {
            return;
        }

        $term->paid = (float) $term->paid + $amount;
        if ($stampPaidDate || ! $term->paid_date) {
            $term->paid_date = $date;
        }
        $term->save();
    }
}
