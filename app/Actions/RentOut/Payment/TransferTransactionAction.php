<?php

namespace App\Actions\RentOut\Payment;

use App\Enums\RentOut\AgreementType;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Move a received payment from one property agreement (RentOut) to another,
 * fully and in place — the whole amount moves, nothing is split.
 *
 * Rather than posting counter-entries, the existing receipt is simply
 * re-homed onto the target agreement:
 *   - the ledger row's rent_out_id (and branch) is re-pointed to the target;
 *   - its journal + entries are re-pointed to the target (and the income leg is
 *     swapped when the two agreements recognise income to different accounts,
 *     e.g. a Rental → Lease move);
 *   - the source payment term it satisfied is freed (its balance re-opens);
 *   - an optional target term is credited, otherwise the payment lands as an
 *     unapplied credit that simply reduces the target's outstanding balance.
 *
 * The move leaves ONE receipt and ONE journal — no residue on the source — and
 * the row's audit trail records where it came from.
 */
class TransferTransactionAction
{
    /**
     * @param  int  $paymentId  the receipt (rent_out_transactions.id) to move
     * @param  int  $toRentOutId  the target RentOut agreement to move it to
     * @param  int|null  $toTermId  optional target payment term on the destination
     */
    public function execute(int $paymentId, int $toRentOutId, ?int $toTermId = null, string $remark = ''): array
    {
        try {
            return DB::transaction(function () use ($paymentId, $toRentOutId, $toTermId, $remark) {
                $payment = RentOutTransaction::findOrFail($paymentId);
                $fromRentOut = RentOut::findOrFail($payment->rent_out_id);
                $toRentOut = RentOut::findOrFail($toRentOutId);

                $this->guard($payment, $fromRentOut, $toRentOut);

                $amount = (float) $payment->credit;
                $today = now()->format('Y-m-d');

                // 1. Free the source term this receipt was covering (balance re-opens).
                $this->releaseSourceTerm($payment);

                // 2. Re-point the journal + entries onto the target agreement.
                $this->moveJournal($payment, $fromRentOut, $toRentOut, $remark);

                // 3. Credit the chosen target term (if any).
                if ($toTermId) {
                    $this->applyTargetTerm($toTermId, $amount, $today);
                }

                // 4. Re-home the ledger row itself onto the target agreement.
                $movedFrom = 'Moved from '.$fromRentOut->agreement_type?->label().' #'.$fromRentOut->id
                    .($fromRentOut->property?->number ? ' (Unit '.$fromRentOut->property->number.')' : '');

                $attributes = [
                    'rent_out_id' => $toRentOut->id,
                    'branch_id' => $toRentOut->branch_id,
                    'remark' => trim(($remark ? $remark.' — ' : '').$movedFrom),
                ];

                if ($toTermId) {
                    $term = RentOutPaymentTerm::find($toTermId);
                    $attributes += [
                        'source' => 'PaymentTerm',
                        'source_id' => $toTermId,
                        'model' => 'RentOutPaymentTerm',
                        'model_id' => $toTermId,
                        'due_date' => $term?->due_date?->format('Y-m-d'),
                        'group' => $toRentOut->agreement_type?->config()->paymentGroupLabel ?? 'Rent Payment',
                        'category' => $term?->label ?? $payment->category,
                    ];
                } else {
                    // Unapplied credit on the target — not tied to a specific term.
                    $attributes += [
                        'source' => 'Transfer',
                        'source_id' => null,
                        'model' => 'RentOut',
                        'model_id' => $toRentOut->id,
                        'group' => 'Payment Transfer',
                    ];
                }

                $payment->update($attributes);

                return [
                    'success' => true,
                    'message' => 'Payment moved successfully',
                    'data' => $payment->fresh(),
                ];
            });
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    protected function guard(RentOutTransaction $payment, RentOut $fromRentOut, RentOut $toRentOut): void
    {
        if ((float) $payment->credit <= 0) {
            throw new \RuntimeException('Only received payments can be transferred.');
        }
        if ($payment->source !== 'PaymentTerm') {
            throw new \RuntimeException('Only rent (payment term) receipts can be transferred.');
        }
        if ($payment->model === 'RentOutCheque') {
            throw new \RuntimeException('Cheque-based payments cannot be transferred; manage the cheque on the original property instead.');
        }
        if ($fromRentOut->id === $toRentOut->id) {
            throw new \RuntimeException('Cannot transfer a payment to the same property.');
        }
        if ($fromRentOut->tenant_id !== $toRentOut->tenant_id) {
            throw new \RuntimeException('Cannot transfer a payment across tenants.');
        }
        if ($fromRentOut->account_id !== $toRentOut->account_id) {
            throw new \RuntimeException('A payment can only be transferred between properties of the same customer.');
        }
    }

    /**
     * Re-point the receipt's journal (and its entries) from the source agreement
     * to the target one. When the two agreements recognise income to different
     * accounts, the income leg is swapped so the books stay correct.
     */
    protected function moveJournal(RentOutTransaction $payment, RentOut $fromRentOut, RentOut $toRentOut, string $remark): void
    {
        if (! $payment->journal_id) {
            return;
        }

        $journal = Journal::find($payment->journal_id);
        if (! $journal) {
            return;
        }

        $journal->update([
            'model_id' => $toRentOut->id,
            'branch_id' => $toRentOut->branch_id,
            'remarks' => trim(($remark ? $remark.' — ' : '').'Transferred from #'.$fromRentOut->id.' to #'.$toRentOut->id),
        ]);

        $fromIncome = $this->incomeAccountId($fromRentOut);
        $toIncome = $this->incomeAccountId($toRentOut);

        foreach (JournalEntry::where('journal_id', $journal->id)->get() as $entry) {
            $update = [
                'model_id' => $toRentOut->id,
                'branch_id' => $toRentOut->branch_id,
            ];
            // Swap the income leg only when the target recognises to a different account.
            if ($fromIncome && $toIncome && $fromIncome !== $toIncome) {
                if ((int) $entry->account_id === $fromIncome) {
                    $update['account_id'] = $toIncome;
                }
                if ((int) $entry->counter_account_id === $fromIncome) {
                    $update['counter_account_id'] = $toIncome;
                }
            }
            $entry->update($update);
        }
    }

    /**
     * Free the source payment term this receipt covered: deduct the amount from
     * its paid total and flip it back to pending when it is no longer covered.
     */
    protected function releaseSourceTerm(RentOutTransaction $payment): void
    {
        $term = null;
        if ($payment->model === 'RentOutPaymentTerm' && $payment->model_id) {
            $term = RentOutPaymentTerm::find($payment->model_id);
        } elseif ($payment->source === 'PaymentTerm' && $payment->source_id) {
            $term = RentOutPaymentTerm::find($payment->source_id);
        }

        if (! $term) {
            return;
        }

        $term->paid = max(0, (float) $term->paid - (float) $payment->credit);
        if ($term->paid <= 0) {
            $term->paid_date = null;
        }
        if ($term->paid < (float) $term->total) {
            $term->status = 'pending';
        }
        $term->save();
    }

    protected function applyTargetTerm(int $termId, float $amount, string $date): void
    {
        $term = RentOutPaymentTerm::find($termId);
        if (! $term) {
            return;
        }

        $term->paid = (float) $term->paid + $amount;
        $term->paid_date = $date;
        // The model's saving hook flips status to 'paid' once fully covered.
        $term->save();
    }

    protected function incomeAccountId(RentOut $rentOut): ?int
    {
        if ($rentOut->agreement_type === AgreementType::Lease) {
            return $this->lockedAccountId($rentOut->tenant_id, 'sale')
                ?? $rentOut->account_id;
        }

        return $this->lockedAccountId($rentOut->tenant_id, 'rent_income')
            ?? $this->lockedAccountId($rentOut->tenant_id, 'sale')
            ?? $rentOut->account_id;
    }

    protected function lockedAccountId(int $tenantId, string $slug): ?int
    {
        return Account::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->where('is_locked', 1)
            ->value('id');
    }
}
