<?php

namespace App\Actions\RentOut\Payment;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Models\RentOut;
use App\Models\RentOutPayment;

class StorePaymentAction
{
    /**
     * Store a RentOutPayment record and create a corresponding journal entry.
     *
     * For receipts (money IN): credit > 0, journal = Dr PaymentMethod, Cr Customer
     * For payouts (money OUT): debit > 0, journal = Dr Customer, Cr PaymentMethod
     */
    public function execute(array $data): array
    {
        try {
            $rentOut = RentOut::findOrFail($data['rent_out_id']);

            $payment = RentOutPayment::create([
                'tenant_id' => $rentOut->tenant_id,
                'branch_id' => $rentOut->branch_id,
                'rent_out_id' => $rentOut->id,
                'date' => $data['date'],
                'credit' => $data['credit'] ?? 0,
                'debit' => $data['debit'] ?? 0,
                'account_id' => $data['account_id'],
                'source' => $data['source'],
                'source_id' => $data['source_id'] ?? null,
                'group' => $data['group'] ?? null,
                'category' => $data['category'] ?? null,
                'payment_type' => $data['payment_type'] ?? null,
                'remark' => $data['remark'] ?? null,
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

            return [
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => $payment,
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
