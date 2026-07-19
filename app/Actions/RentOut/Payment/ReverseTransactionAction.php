<?php

namespace App\Actions\RentOut\Payment;

use App\Enums\RentOut\ChequeStatus;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\RentOutCheque;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;

/**
 * Single source of truth for undoing a rent_out_transactions row.
 *
 * A receipt/payout is never a standalone record — recording it also created a
 * balanced journal (with entries that drive account balances) and, for receipts,
 * advanced a payment term's paid total (and possibly cleared a cheque). Deleting
 * the ledger row alone therefore leaves the books inflated and the term/cheque
 * stuck in a paid/cleared state. This action reverses every side effect so any
 * caller (payment delete, cheque delete, term delete, agreement delete) rolls
 * back the whole chain consistently.
 */
class ReverseTransactionAction
{
    /**
     * Reverse a single transaction: undo its term/cheque side effects, then
     * delete its journal (with entries) and the transaction itself.
     */
    public function reverse(RentOutTransaction $payment, bool $resetCheque = true): void
    {
        // A payment transfer is two ledger rows sharing one journal; unwinding
        // either must restore both terms and remove both rows as a single unit.
        if ($payment->source === 'Transfer') {
            $this->reverseTransfer($payment);

            return;
        }

        $this->rollbackSideEffects($payment, $resetCheque);

        if ($payment->journal_id) {
            JournalEntry::where('journal_id', $payment->journal_id)->delete();
            Journal::where('id', $payment->journal_id)->delete();
        }

        $payment->delete();
    }

    /**
     * Unwind a payment transfer (see TransferTransactionAction): re-charge the
     * source term (undo its release), roll back the target term's paid total,
     * then delete the shared journal and both mirrored ledger rows.
     */
    public function reverseTransfer(RentOutTransaction $payment): void
    {
        $rows = $payment->journal_id
            ? RentOutTransaction::where('journal_id', $payment->journal_id)->get()
            : collect([$payment]);

        foreach ($rows as $row) {
            if ($row->category === 'transfer_out' && $row->debit > 0) {
                // Restore the source term the transfer had freed. The originating
                // receipt is referenced by source_id; re-add the amount to its term.
                $origin = $row->source_id ? RentOutTransaction::find($row->source_id) : null;
                $term = $this->resolveTerm($origin);
                if ($term) {
                    $term->paid = (float) $term->paid + (float) $row->debit;
                    if ($term->paid > 0 && ! $term->paid_date) {
                        $term->paid_date = $row->date;
                    }
                    $term->save();
                }
            }

            if ($row->category === 'transfer_in' && $row->credit > 0) {
                // Roll back the target term's paid total (reuses the shared helper).
                $this->rollbackSideEffects($row, resetCheque: false);
            }
        }

        if ($payment->journal_id) {
            JournalEntry::where('journal_id', $payment->journal_id)->delete();
            Journal::where('id', $payment->journal_id)->delete();
        }

        RentOutTransaction::whereIn('id', $rows->pluck('id'))->delete();
    }

    /**
     * Resolve the payment term a receipt was applied to, whether it is linked by
     * model (RentOutPaymentTerm) or by source (PaymentTerm/source_id).
     */
    protected function resolveTerm(?RentOutTransaction $payment): ?RentOutPaymentTerm
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
     * Reverse every transaction attached to a cheque (its clearance receipt).
     */
    public function reverseForCheque(RentOutCheque $cheque): void
    {
        RentOutTransaction::where('model', 'RentOutCheque')
            ->where('model_id', $cheque->id)
            ->get()
            // The cheque is being removed, so don't bother resetting its status.
            ->each(fn (RentOutTransaction $payment) => $this->reverse($payment, resetCheque: false));
    }

    /**
     * Reverse every transaction that paid a payment term — whether it was a
     * direct term receipt (model = RentOutPaymentTerm) or a cheque clearance
     * that credited the term (source = PaymentTerm / source_id = term id).
     */
    public function reverseForTerm(RentOutPaymentTerm $term): void
    {
        RentOutTransaction::where('rent_out_id', $term->rent_out_id)
            ->where(function ($q) use ($term) {
                $q->where(function ($q) use ($term) {
                    $q->where('model', 'RentOutPaymentTerm')->where('model_id', $term->id);
                })->orWhere(function ($q) use ($term) {
                    $q->where('source', 'PaymentTerm')->where('source_id', $term->id);
                });
            })
            ->get()
            ->each(fn (RentOutTransaction $payment) => $this->reverse($payment));
    }

    /**
     * Undo the term/cheque state a receipt applied when it was recorded:
     *  - deduct its amount from the payment term's paid total (paid → balance)
     *    and flip the term back to pending when a balance remains;
     *  - reset a cheque-clearance cheque back to uncleared.
     *
     * Payouts (money OUT) carry no term/cheque state, so they are skipped.
     */
    public function rollbackSideEffects(RentOutTransaction $payment, bool $resetCheque = true): void
    {
        $amount = (float) $payment->credit;
        if ($amount <= 0) {
            return;
        }

        $term = null;
        if ($payment->model === 'RentOutPaymentTerm' && $payment->model_id) {
            $term = RentOutPaymentTerm::find($payment->model_id);
        } elseif ($payment->source === 'PaymentTerm' && $payment->source_id) {
            $term = RentOutPaymentTerm::find($payment->source_id);
        }

        if ($term) {
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

        if ($resetCheque && $payment->model === 'RentOutCheque' && $payment->model_id) {
            RentOutCheque::where('id', $payment->model_id)
                ->update(['status' => ChequeStatus::Uncleared->value]);
        }
    }
}
