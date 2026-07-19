<?php

namespace App\Actions\RentOut\Payment;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Enums\RentOut\AgreementType;
use App\Models\Account;
use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Transfer a received payment from one property agreement (RentOut) to another
 * WITHOUT moving cash a second time.
 *
 * The customer's money was received once and stays in the bank; a transfer only
 * re-allocates the recognised value from the source property to the target one.
 * We therefore post a single income-to-income journal:
 *
 *      Dr  Rent/Sale Income (source property)   amount
 *      Cr  Rent/Sale Income (target property)   amount
 *
 * and mirror it with two rent_out_transactions ledger rows that share that one
 * journal (via storeContraRow, so no duplicate journal is created):
 *
 *   - source ledger: a DEBIT row (reverses the earlier receipt → source owes again)
 *   - target ledger: a CREDIT row (applies the payment to the target)
 *
 * Both rows carry source = 'Transfer' and share journal_id, which is the grouping
 * key ReverseTransactionAction uses to unwind the whole movement as one unit.
 */
class TransferTransactionAction
{
    /**
     * @param  int  $paymentId  the source receipt (rent_out_transactions.id) to transfer from
     * @param  int  $toRentOutId  the target RentOut agreement to transfer to
     * @param  float  $amount  how much to transfer (supports partial transfers)
     * @param  int|null  $toTermId  optional target payment term on the destination agreement
     */
    public function execute(int $paymentId, int $toRentOutId, float $amount, ?int $toTermId = null, string $remark = '', ?string $date = null): array
    {
        try {
            return DB::transaction(function () use ($paymentId, $toRentOutId, $amount, $toTermId, $remark, $date) {
                $payment = RentOutTransaction::findOrFail($paymentId);
                $fromRentOut = RentOut::findOrFail($payment->rent_out_id);
                $toRentOut = RentOut::findOrFail($toRentOutId);

                $this->guard($payment, $fromRentOut, $toRentOut, $amount);

                $date ??= now()->format('Y-m-d');
                $store = new StoreTransactionAction();
                $store2 = $store; // same instance; storeContraRow is stateless

                $fromIncomeAccountId = $this->incomeAccountId($fromRentOut);
                $toIncomeAccountId = $this->incomeAccountId($toRentOut);
                $createdBy = Auth::id() ?? $fromRentOut->created_by;

                // One balanced journal: Dr source income, Cr target income (no cash leg).
                $journalData = [
                    'tenant_id' => $fromRentOut->tenant_id,
                    'branch_id' => $fromRentOut->branch_id,
                    'date' => $date,
                    'description' => 'Payment Transfer',
                    'remarks' => $remark ?: ('Transfer from RentOut #'.$fromRentOut->id.' to #'.$toRentOut->id),
                    'source' => 'transfer',
                    'model' => 'RentOut',
                    'model_id' => $fromRentOut->id,
                    'created_by' => $createdBy,
                    'entries' => [
                        [
                            'account_id' => $fromIncomeAccountId,
                            'counter_account_id' => $toIncomeAccountId,
                            'debit' => $amount,
                            'credit' => 0,
                            'created_by' => $createdBy,
                            'remarks' => $remark,
                            'model' => 'RentOut',
                            'model_id' => $fromRentOut->id,
                        ],
                        [
                            'account_id' => $toIncomeAccountId,
                            'counter_account_id' => $fromIncomeAccountId,
                            'debit' => 0,
                            'credit' => $amount,
                            'created_by' => $createdBy,
                            'remarks' => $remark,
                            'model' => 'RentOut',
                            'model_id' => $toRentOut->id,
                        ],
                    ],
                ];

                $journalResponse = (new JournalCreateAction())->execute($journalData);
                if (! $journalResponse['success']) {
                    throw new \Exception($journalResponse['message']);
                }
                $journalId = $journalResponse['data']->id;

                // Source ledger leg: DEBIT reverses the earlier receipt on the source.
                $fromRow = $store->storeContraRow([
                    'rent_out_id' => $fromRentOut->id,
                    'date' => $date,
                    'debit' => $amount,
                    'credit' => 0,
                    'account_id' => $payment->account_id,
                    'journal_id' => $journalId,
                    'source' => 'Transfer',
                    'source_id' => $payment->id,
                    'model' => 'RentOut',
                    'model_id' => $toRentOut->id,
                    'group' => 'Payment Transfer',
                    'category' => 'transfer_out',
                    'payment_type' => 'Transfer',
                    'paid_date' => $date,
                    'reason' => 'Transferred to RentOut #'.$toRentOut->id,
                    'remark' => $remark ?: ('Transferred to '.($toRentOut->property?->name ?? ('RentOut #'.$toRentOut->id))),
                    'created_by' => $createdBy,
                ]);
                if (! $fromRow['success']) {
                    throw new \Exception($fromRow['message']);
                }

                // Target ledger leg: CREDIT applies the payment to the destination.
                // When a target term is chosen, link the row to it so a later reversal
                // rolls the term's paid total back (ReverseTransactionAction handles this).
                $toRow = $store2->storeContraRow([
                    'rent_out_id' => $toRentOut->id,
                    'date' => $date,
                    'debit' => 0,
                    'credit' => $amount,
                    'account_id' => $payment->account_id,
                    'journal_id' => $journalId,
                    'source' => 'Transfer',
                    'source_id' => $fromRow['data']->id,
                    'model' => $toTermId ? 'RentOutPaymentTerm' : 'RentOut',
                    'model_id' => $toTermId ?: $toRentOut->id,
                    'group' => 'Payment Transfer',
                    'category' => 'transfer_in',
                    'payment_type' => 'Transfer',
                    'paid_date' => $date,
                    'reason' => 'Transferred from RentOut #'.$fromRentOut->id,
                    'remark' => $remark ?: ('Transferred from '.($fromRentOut->property?->name ?? ('RentOut #'.$fromRentOut->id))),
                    'created_by' => $createdBy,
                ]);
                if (! $toRow['success']) {
                    throw new \Exception($toRow['message']);
                }

                // Free the source term (the money no longer sits against it).
                $this->releaseSourceTerm($payment, $amount);

                // Advance the target term's paid total.
                if ($toTermId) {
                    $this->applyTargetTerm($toTermId, $amount, $date);
                }

                return [
                    'success' => true,
                    'message' => 'Payment transferred successfully',
                    'data' => ['from' => $fromRow['data'], 'to' => $toRow['data'], 'journal_id' => $journalId],
                ];
            });
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * How much of a receipt is still available to transfer (its credit minus any
     * amount already transferred out of it).
     */
    public function transferableAmount(RentOutTransaction $payment): float
    {
        $alreadyTransferred = (float) RentOutTransaction::query()
            ->where('source', 'Transfer')
            ->where('source_id', $payment->id)
            ->where('rent_out_id', $payment->rent_out_id)
            ->where('debit', '>', 0)
            ->sum('debit');

        return max(0, (float) $payment->credit - $alreadyTransferred);
    }

    protected function guard(RentOutTransaction $payment, RentOut $fromRentOut, RentOut $toRentOut, float $amount): void
    {
        if ((float) $payment->credit <= 0) {
            throw new \RuntimeException('Only received payments can be transferred.');
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
        if ($amount <= 0) {
            throw new \RuntimeException('Transfer amount must be greater than zero.');
        }
        if ($amount > $this->transferableAmount($payment) + 0.001) {
            throw new \RuntimeException('Transfer amount exceeds the available balance of this payment.');
        }
    }

    /**
     * Reduce the source payment term's paid total by the transferred amount and
     * flip it back to pending when it is no longer fully covered. Mirrors
     * ReverseTransactionAction::rollbackSideEffects but scoped to a partial amount.
     */
    protected function releaseSourceTerm(RentOutTransaction $payment, float $amount): void
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

        $term->paid = max(0, (float) $term->paid - $amount);
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
