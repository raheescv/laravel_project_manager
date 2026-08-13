<?php

namespace App\Actions\RentOut\Payment;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Actions\Journal\UpdateAction as JournalUpdateAction;
use App\Actions\RentOut\Payment\Concerns\ResolvesRentOutAccounts;
use App\Models\Journal;
use App\Models\RentOut;
use App\Models\RentOutTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StoreTransactionAction
{
    use ResolvesRentOutAccounts;

    /**
     * Charge customer (debit entry only — no payment received yet).
     * Used for Pay Later / Service Charge scenarios.
     *
     * Journal: Dr Customer (receivable), Cr Income. The income account is the
     * category chosen on the modal when there is one, otherwise the tenant's
     * locked service/sale income account.
     */
    public function charge(int $rentOutId, array $data): array
    {
        $rentOut = RentOut::findOrFail($rentOutId);

        return $this->execute(array_merge($data, [
            'rent_out_id' => $rentOutId,
            'credit' => 0,
            'debit' => $data['amount'],
            'account_id' => $rentOut->account_id,
            'counter_account_id' => $data['counter_account_id'] ?? $this->resolveChargeIncomeAccountId($rentOut, $data),
            'journal_source' => $data['journal_source'] ?? 'income',
            // A charge is not money leaving the business: the row's own account
            // (the customer) is the debit side, the income account the credit side.
            'journal_is_payout' => false,
        ]));
    }

    /**
     * Receive payment (credit entry only — payment received).
     * Used when recording a receipt against an existing charge.
     */
    public function receive(int $rentOutId, array $data): array
    {
        return $this->execute(array_merge($data, [
            'rent_out_id' => $rentOutId,
            'credit' => $data['amount'],
            'debit' => 0,
        ]));
    }

    /**
     * Charge and receive payment in one go (debit + credit entries).
     * Used for Pay Now scenarios.
     */
    public function chargeAndPay(int $rentOutId, array $data): array
    {
        $rentOut = RentOut::findOrFail($rentOutId);

        // Entry 1: Debit — charge to customer (Dr Customer, Cr Income)
        $chargeResponse = $this->charge($rentOutId, $data);
        if (! $chargeResponse['success']) {
            return $chargeResponse;
        }

        // Entry 2: Credit — payment received. The charge leg already recognised
        // the income, so this leg only settles the receivable it created:
        // Dr Payment Method, Cr Customer. Countering to income here instead
        // would recognise the same revenue twice.
        $receiveData = array_merge($data, [
            'group' => ($data['group'] ?? 'Service').' Payment',
            'counter_account_id' => $rentOut->account_id,
            'journal_source' => $rentOut->agreement_type?->sourceSlug() ?? 'rent_out',
        ]);

        return $this->receive($rentOutId, $receiveData);
    }

    /**
     * Revert (reverse) an existing payment by creating opposite entries.
     */
    public function revert(int $paymentId): array
    {
        try {
            $payment = RentOutTransaction::findOrFail($paymentId);

            return $this->execute([
                'rent_out_id' => $payment->rent_out_id,
                'date' => now()->format('Y-m-d'),
                'credit' => $payment->debit,
                'debit' => $payment->credit,
                'account_id' => $payment->account_id,
                'source' => $payment->source,
                'source_id' => $payment->source_id,
                'group' => $payment->group,
                'category' => $payment->category,
                'payment_type' => $payment->payment_type,
                'remark' => 'Reversal: '.($payment->remark ?? ''),
                'created_by' => Auth::id(),
            ]);
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Update an existing payment record.
     */
    public function update(int $paymentId, array $data): array
    {
        try {
            $payment = RentOutTransaction::findOrFail($paymentId);

            $newDebit = $payment->debit > 0 ? ($data['amount'] ?? $payment->debit) : 0;
            $newCredit = $payment->credit > 0 ? ($data['amount'] ?? $payment->credit) : 0;

            $payment->update([
                'date' => $data['date'] ?? $payment->date,
                'debit' => $newDebit,
                'credit' => $newCredit,
                'category' => $data['category'] ?? $payment->category,
                // A charge row is the customer's receivable — the payment mode
                // picked on the modal belongs to the receipt row, not this one.
                'account_id' => $this->isChargeRow($payment)
                    ? $payment->account_id
                    : ($data['account_id'] ?? $payment->account_id),
                'remark' => $data['remark'] ?? $payment->remark,
                'reason' => $data['reason'] ?? $payment->reason,
                'cheque_no' => $data['cheque_no'] ?? $payment->cheque_no,
                'cheque_date' => $data['cheque_date'] ?? $payment->cheque_date,
                'bank_name' => $data['bank_name'] ?? $payment->bank_name,
            ]);

            // Re-post the journal so a changed payment method or category moves
            // the entries too, not just their amounts.
            if ($payment->journal_id) {
                $journalResponse = $this->syncJournalEntries($payment->fresh());
                if (! $journalResponse['success']) {
                    throw new \Exception($journalResponse['message']);
                }
            }

            return [
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => $payment->fresh(),
            ];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Store a RentOutTransaction record and create a corresponding journal entry.
     *
     * For receipts (money IN): credit > 0, journal = Dr PaymentMethod, Cr Customer
     * For payouts (money OUT): debit > 0, journal = Dr Customer, Cr PaymentMethod
     * For charges (no cash):   debit > 0 with journal_is_payout = false,
     *                          journal = Dr Customer, Cr Income
     */
    public function execute(array $data): array
    {
        try {
            $rentOut = RentOut::findOrFail($data['rent_out_id']);
            $createdBy = $this->resolveCreatedBy($rentOut, $data);

            $payment = RentOutTransaction::create($this->buildTransactionAttributes($rentOut, $data, $createdBy));

            // Create journal entry
            $amount = max($data['credit'] ?? 0, $data['debit'] ?? 0);
            // A debit row normally means money left the business, but a charge
            // debits the customer without any cash moving — those callers say so
            // explicitly rather than being inferred from the debit column.
            $isPayout = array_key_exists('journal_is_payout', $data)
                ? (bool) $data['journal_is_payout']
                : ($data['debit'] ?? 0) > 0;

            $journalMetadata = $this->resolveJournalMetadata($rentOut, $data, $isPayout);

            $journalData = [
                'tenant_id' => $rentOut->tenant_id,
                'branch_id' => $rentOut->branch_id,
                'date' => $data['date'],
                'description' => $data['group'] ?? $data['source'],
                'remarks' => $data['remark'] ?? '',
                'source' => $journalMetadata['source'],
                'model' => 'RentOut',
                'model_id' => $rentOut->id,
                'created_by' => $createdBy,
                'entries' => $this->makeEntryPair(
                    $rentOut,
                    (int) $data['account_id'],
                    $amount,
                    $isPayout,
                    $journalMetadata['counter_account_id'],
                    $data['remark'] ?? '',
                    $createdBy
                ),
            ];

            $journalResponse = (new JournalCreateAction())->execute($journalData);
            if (! $journalResponse['success']) {
                throw new \Exception($journalResponse['message']);
            }

            // Store journal reference back on the payment record
            $journal = $journalResponse['data'];
            $payment->update([
                'journal_id' => $journal->id,
            ]);

            return [
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => $payment->fresh(),
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    /**
     * A billed-but-not-yet-collected row: it debits the customer without any
     * cash moving, unlike a payout which also carries a debit.
     */
    protected function isChargeRow(RentOutTransaction $payment): bool
    {
        return $payment->debit > 0 && in_array($payment->source, ['Service', 'ServiceCharge'], true);
    }

    /**
     * Rewrite a transaction's journal entries from its current state, keeping
     * the journal itself (and every reference to it) intact.
     */
    protected function syncJournalEntries(RentOutTransaction $payment): array
    {
        $journal = Journal::find($payment->journal_id);
        if (! $journal) {
            return ['success' => true, 'message' => 'No journal to sync'];
        }

        $rentOut = RentOut::findOrFail($payment->rent_out_id);
        $amount = (float) max($payment->credit, $payment->debit);
        $isCharge = $this->isChargeRow($payment);
        $isPayout = $payment->debit > 0 && ! $isCharge;

        if ($isCharge) {
            // Dr Customer, Cr Income (the chosen category).
            $accountId = (int) $rentOut->account_id;
            $counterAccountId = $this->resolveChargeIncomeAccountId($rentOut, [
                'income_account_id' => is_numeric($payment->category) ? (int) $payment->category : null,
                'source' => $payment->source,
            ]);
        } else {
            // Receipt: Dr Payment Method, Cr Customer. Payout: the mirror.
            $accountId = (int) $payment->account_id;
            $counterAccountId = (int) $rentOut->account_id;
        }

        $entries = $this->makeEntryPair(
            $rentOut,
            $accountId,
            $amount,
            $isPayout,
            $counterAccountId,
            $payment->remark ?? '',
            (int) $payment->created_by
        );

        return (new JournalUpdateAction())->execute([
            'branch_id' => $journal->branch_id,
            'date' => $payment->date,
            'description' => $journal->description,
            'remarks' => $payment->remark ?? $journal->remarks,
            'created_by' => $journal->created_by,
            'entries' => $entries,
        ], $journal->id);
    }

    /**
     * Store an additional ledger row (a contra leg) that reuses an already
     * created journal. Used to mirror the second side of a double-entry movement
     * into rent_out_transactions without generating a duplicate journal.
     */
    public function storeContraRow(array $data): array
    {
        try {
            $rentOut = RentOut::findOrFail($data['rent_out_id']);
            $createdBy = $this->resolveCreatedBy($rentOut, $data);

            $payment = RentOutTransaction::create($this->buildTransactionAttributes($rentOut, $data, $createdBy));

            return [
                'success' => true,
                'message' => 'Ledger row recorded successfully',
                'data' => $payment->fresh(),
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    /**
     * Build the RentOutTransaction attribute array from a payment data payload.
     */
    protected function buildTransactionAttributes(RentOut $rentOut, array $data, int $createdBy): array
    {
        return [
            'tenant_id' => $rentOut->tenant_id,
            'branch_id' => $rentOut->branch_id,
            'rent_out_id' => $rentOut->id,
            'date' => $data['date'],
            'due_date' => $data['due_date'] ?? null,
            'paid_date' => $data['paid_date'] ?? null,
            'cheque_date' => $data['cheque_date'] ?? null,
            'cheque_no' => $data['cheque_no'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'credit' => $data['credit'] ?? 0,
            'debit' => $data['debit'] ?? 0,
            'account_id' => $data['account_id'],
            'source' => $data['source'],
            'source_id' => $data['source_id'] ?? null,
            'model' => $data['model'] ?? null,
            'model_id' => $data['model_id'] ?? null,
            'journal_id' => $data['journal_id'] ?? null,
            'group' => $data['group'] ?? null,
            'category' => $data['category'] ?? null,
            'payment_type' => $data['payment_type'] ?? null,
            'remark' => $data['remark'] ?? null,
            'reason' => $data['reason'] ?? null,
            'voucher_no' => $data['voucher_no'] ?? null,
            'created_by' => $createdBy,
        ];
    }

    /**
     * Create debit/credit entry pair for the journal.
     *
     * Receipt: Dr PaymentMethod, Cr Customer
     * Payout:  Dr Customer,      Cr PaymentMethod
     */
    protected function makeEntryPair(
        RentOut $rentOut,
        int $paymentAccountId,
        float $amount,
        bool $isPayout,
        ?int $counterAccountId,
        string $remarks,
        int $createdBy
    ): array {
        $counterAccountId ??= $rentOut->account_id;

        // Both legs landing on the same account is never a real posting — it
        // nets to nothing and hides the account that should have been resolved.
        if ((int) $counterAccountId === $paymentAccountId) {
            throw new \RuntimeException('Cannot post a journal against a single account (account #'.$paymentAccountId.'); the counter account could not be resolved.');
        }

        $base = [
            'created_by' => $createdBy,
            'remarks' => $remarks,
            'model' => 'RentOut',
            'model_id' => $rentOut->id,
        ];

        if ($isPayout) {
            // Money going OUT: Dr Counter Account, Cr Payment Method
            return [
                array_merge($base, [
                    'account_id' => $counterAccountId,
                    'counter_account_id' => $paymentAccountId,
                    'debit' => $amount,
                    'credit' => 0,
                ]),
                array_merge($base, [
                    'account_id' => $paymentAccountId,
                    'counter_account_id' => $counterAccountId,
                    'debit' => 0,
                    'credit' => $amount,
                ]),
            ];
        }

        // Money coming IN: Dr PaymentMethod, Cr Counter Account
        return [
            array_merge($base, [
                'account_id' => $paymentAccountId,
                'counter_account_id' => $counterAccountId,
                'debit' => $amount,
                'credit' => 0,
            ]),
            array_merge($base, [
                'account_id' => $counterAccountId,
                'counter_account_id' => $paymentAccountId,
                'debit' => 0,
                'credit' => $amount,
            ]),
        ];
    }

    protected function resolveJournalMetadata(RentOut $rentOut, array $data, bool $isPayout): array
    {
        // Explicit counter account (e.g. Security Deposit liability) overrides the
        // default customer/income routing for both receipts and payouts.
        if (! empty($data['counter_account_id'])) {
            return [
                'source' => $data['journal_source'] ?? ($isPayout ? 'expense' : ($rentOut->agreement_type?->sourceSlug() ?? 'rent_out')),
                'counter_account_id' => (int) $data['counter_account_id'],
            ];
        }

        if ($isPayout) {
            return [
                'source' => 'expense',
                'counter_account_id' => $rentOut->account_id,
            ];
        }

        $source = (string) ($data['source'] ?? '');

        if (in_array($source, ['PaymentTerm', 'UtilityTerm', 'Service', 'ServiceCharge'], true)) {
            return [
                'source' => 'income',
                'counter_account_id' => $this->incomeAccountIdFor($rentOut, $source),
            ];
        }

        return [
            'source' => $rentOut->agreement_type?->sourceSlug() ?? 'rent_out',
            'counter_account_id' => $rentOut->account_id,
        ];
    }

    /**
     * Rent recognises to the property income account; the services and
     * utilities billed alongside it recognise to the service income account.
     */
    protected function incomeAccountIdFor(RentOut $rentOut, string $source): ?int
    {
        return $source === 'PaymentTerm'
            ? $this->propertyIncomeAccountId($rentOut)
            : $this->serviceIncomeAccountId($rentOut);
    }

    /**
     * Income account credited by a charge leg. The category picked on the modal
     * is an account id, so it wins when it belongs to this tenant; otherwise
     * fall back to the module's locked income account.
     */
    protected function resolveChargeIncomeAccountId(RentOut $rentOut, array $data): ?int
    {
        return $this->tenantAccountId($rentOut, $data['income_account_id'] ?? null)
            ?? $this->incomeAccountIdFor($rentOut, (string) ($data['source'] ?? ''));
    }

    protected function resolveCreatedBy(RentOut $rentOut, array $data): int
    {
        $createdBy = $data['created_by'] ?? $rentOut->created_by ?? Auth::id();

        if (is_numeric($createdBy)) {
            return (int) $createdBy;
        }

        $tenantUserId = User::query()
            ->where('tenant_id', $rentOut->tenant_id)
            ->orderBy('id')
            ->value('id');

        if ($tenantUserId !== null) {
            return (int) $tenantUserId;
        }

        throw new \RuntimeException('Unable to resolve created_by for property payment transaction.');
    }
}
