<?php

namespace App\Actions\RentOut\Payment;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Models\Journal;
use App\Models\RentOut;
use App\Models\RentOutTransaction;
use Illuminate\Support\Facades\Auth;

class StoreTransactionAction
{
    /**
     * Charge customer (debit entry only — no payment received yet).
     * Used for Pay Later / Service Charge scenarios.
     */
    public function charge(int $rentOutId, array $data): array
    {
        $rentOut = RentOut::findOrFail($rentOutId);

        return $this->execute(array_merge($data, [
            'rent_out_id' => $rentOutId,
            'credit' => 0,
            'debit' => $data['amount'],
            'account_id' => $rentOut->account_id,
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
        // Entry 1: Debit — charge to customer
        $chargeResponse = $this->charge($rentOutId, $data);
        if (! $chargeResponse['success']) {
            return $chargeResponse;
        }

        // Entry 2: Credit — payment received
        $receiveData = array_merge($data, [
            'group' => ($data['group'] ?? 'Service').' Payment',
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
                'account_id' => $data['account_id'] ?? $payment->account_id,
                'remark' => $data['remark'] ?? $payment->remark,
                'reason' => $data['reason'] ?? $payment->reason,
                'cheque_no' => $data['cheque_no'] ?? $payment->cheque_no,
                'cheque_date' => $data['cheque_date'] ?? $payment->cheque_date,
                'bank_name' => $data['bank_name'] ?? $payment->bank_name,
            ]);

            // Sync journal entries if journal exists
            if ($payment->journal_id) {
                $amount = max($newCredit, $newDebit);
                $journal = Journal::find($payment->journal_id);
                if ($journal) {
                    $journal->entries()->each(function ($entry) use ($amount) {
                        if ($entry->debit > 0) {
                            $entry->update(['debit' => $amount]);
                        }
                        if ($entry->credit > 0) {
                            $entry->update(['credit' => $amount]);
                        }
                    });
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
     */
    public function execute(array $data): array
    {
        try {
            $rentOut = RentOut::findOrFail($data['rent_out_id']);

            $payment = RentOutTransaction::create([
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
                'group' => $data['group'] ?? null,
                'category' => $data['category'] ?? null,
                'payment_type' => $data['payment_type'] ?? null,
                'remark' => $data['remark'] ?? null,
                'reason' => $data['reason'] ?? null,
                'voucher_no' => $data['voucher_no'] ?? null,
                'created_by' => $data['created_by'] ?? $rentOut->created_by,
            ]);

            // Create journal entry
            $amount = max($data['credit'] ?? 0, $data['debit'] ?? 0);
            $isPayout = ($data['debit'] ?? 0) > 0;

            $journalData = [
                'tenant_id' => $rentOut->tenant_id,
                'branch_id' => $rentOut->branch_id,
                'date' => $data['date'],
                'description' => $data['group'] ?? $data['source'],
                'remarks' => $data['remark'] ?? '',
                'source' => 'rent_out',
                'model' => 'RentOut',
                'model_id' => $rentOut->id,
                'created_by' => $data['created_by'] ?? $rentOut->created_by,
                'entries' => $this->makeEntryPair(
                    $rentOut,
                    (int) $data['account_id'],
                    $amount,
                    $isPayout,
                    $data['remark'] ?? '',
                    $data['created_by'] ?? $rentOut->created_by
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
        string $remarks,
        int $createdBy
    ): array {
        $customerId = $rentOut->account_id;
        $base = [
            'created_by' => $createdBy,
            'remarks' => $remarks,
            'model' => 'RentOut',
            'model_id' => $rentOut->id,
        ];

        if ($isPayout) {
            // Money going OUT: Dr Customer, Cr PaymentMethod
            return [
                array_merge($base, [
                    'account_id' => $customerId,
                    'counter_account_id' => $paymentAccountId,
                    'debit' => $amount,
                    'credit' => 0,
                ]),
                array_merge($base, [
                    'account_id' => $paymentAccountId,
                    'counter_account_id' => $customerId,
                    'debit' => 0,
                    'credit' => $amount,
                ]),
            ];
        }

        // Money coming IN: Dr PaymentMethod, Cr Customer
        return [
            array_merge($base, [
                'account_id' => $paymentAccountId,
                'counter_account_id' => $customerId,
                'debit' => $amount,
                'credit' => 0,
            ]),
            array_merge($base, [
                'account_id' => $customerId,
                'counter_account_id' => $paymentAccountId,
                'debit' => 0,
                'credit' => $amount,
            ]),
        ];
    }
}
