<?php

namespace App\Actions\RentOut\Payment;

use App\Actions\RentOut\Payment\Concerns\AppliesPaymentTerms;
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
    use AppliesPaymentTerms;

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
        $this->deleteJournal($payment->journal_id);

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
                $this->creditTerm(
                    $this->termFor($origin),
                    (float) $row->debit,
                    $row->date,
                    stampPaidDate: false
                );
            }

            if ($row->category === 'transfer_in' && $row->credit > 0) {
                // Roll back the target term's paid total (reuses the shared helper).
                $this->rollbackSideEffects($row, resetCheque: false);
            }
        }

        $this->deleteJournal($payment->journal_id);

        RentOutTransaction::whereIn('id', $rows->pluck('id'))->delete();
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

        $this->releaseTerm($this->termFor($payment), $amount);

        if ($resetCheque && $payment->model === 'RentOutCheque' && $payment->model_id) {
            RentOutCheque::where('id', $payment->model_id)
                ->update(['status' => ChequeStatus::Uncleared->value]);
        }
    }

    /**
     * Remove a journal and its entries. A rent-out journal always belongs to
     * the row(s) being reversed, so it goes with them.
     */
    protected function deleteJournal(?int $journalId): void
    {
        if (! $journalId) {
            return;
        }

        JournalEntry::where('journal_id', $journalId)->delete();
        Journal::where('id', $journalId)->delete();
    }
}
