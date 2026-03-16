<?php

namespace App\Actions\RentOut\Cheque;

use App\Actions\RentOut\Payment\StorePaymentAction;
use App\Models\RentOutCheque;
use App\Models\RentOutPaymentTerm;

class UpdateStatusAction
{
    /**
     * Update cheque status.
     * Supports two calling patterns:
     * 1. execute($id, 'cleared') — from ChequesTab (simple status string)
     * 2. execute($chequeOrId, ['status' => 'cleared', 'payment_method' => ..., ...]) — from ChequeManagementTable
     */
    public function execute($chequeOrId, $statusOrData)
    {
        try {
            // Resolve the cheque model
            if ($chequeOrId instanceof RentOutCheque) {
                $model = $chequeOrId;
            } else {
                $model = RentOutCheque::find($chequeOrId);
            }

            if (! $model) {
                throw new \Exception("RentOut Cheque not found with the specified ID: {$chequeOrId}.", 1);
            }

            // Resolve status and extra data
            if (is_array($statusOrData)) {
                $status = $statusOrData['status'] ?? $statusOrData[0] ?? null;
                $paymentMethodId = $statusOrData['payment_method'] ?? null;
                $journalDate = $statusOrData['journal_date'] ?? null;
                $remark = $statusOrData['remark'] ?? null;
            } else {
                $status = $statusOrData;
                $paymentMethodId = null;
                $journalDate = null;
                $remark = null;
            }

            // Update the cheque status
            $model->update(['status' => $status]);

            // When cheque is cleared, try to pay matching payment term
            $termResult = null;
            if ($status === 'cleared') {
                $termResult = $this->handleChequeCleared($model, $paymentMethodId, $journalDate, $remark);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Cheque Status';
            $return['data'] = $model;
            $return['term_result'] = $termResult;

            if ($termResult && $termResult['paid']) {
                $return['message'] = 'Cheque cleared and payment term paid successfully.';
            } elseif ($termResult && ! $termResult['paid'] && ! empty($termResult['available_terms'])) {
                $return['has_unpaid_terms'] = true;
                $return['available_terms'] = $termResult['available_terms'];
                $return['message'] = 'Cheque cleared. No matching payment term by date — please select a term to pay.';
            }
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * When a cheque is cleared, find the matching payment term by date and pay it.
     * If no match, return available unpaid terms for user selection.
     */
    protected function handleChequeCleared(RentOutCheque $cheque, $paymentMethodId = null, $journalDate = null, $remark = null): array
    {
        // Find payment term matching the cheque date
        $matchingTerm = RentOutPaymentTerm::where('rent_out_id', $cheque->rent_out_id)
            ->whereDate('due_date', $cheque->date)
            ->where('balance', '>', 0)
            ->first();

        if ($matchingTerm) {
            $this->payTermWithCheque($cheque, $matchingTerm, $paymentMethodId, $journalDate, $remark);

            return ['paid' => true, 'term_id' => $matchingTerm->id];
        }

        // No match — return available unpaid terms
        $unpaidTerms = RentOutPaymentTerm::where('rent_out_id', $cheque->rent_out_id)
            ->where('balance', '>', 0)
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(fn ($term) => [
                'id' => $term->id,
                'label' => $term->label ?? '',
                'due_date' => $term->due_date?->format('d-m-Y') ?? '',
                'amount' => number_format($term->total, 2),
                'balance' => number_format($term->balance, 2),
                'raw_balance' => (float) $term->balance,
            ])
            ->toArray();

        return ['paid' => false, 'available_terms' => $unpaidTerms];
    }

    /**
     * Pay a payment term using cheque details.
     */
    public function payTermWithCheque(RentOutCheque $cheque, RentOutPaymentTerm $term, $paymentMethodId = null, $journalDate = null, $remark = null): array
    {
        $payAmount = min($cheque->amount, $term->balance);

        // Update payment term
        $term->paid = ($term->paid ?? 0) + $payAmount;
        $term->payment_mode = 'cheque';
        $term->cheque_no = $cheque->cheque_no;
        $term->paid_date = $journalDate ?? $cheque->date;
        $term->save();

        // Resolve payment method account
        if (! $paymentMethodId) {
            $paymentMethods = paymentMethodsOptions();
            $paymentMethodId = array_key_first($paymentMethods) ?? 1;
        }

        // Create payment record
        $data = [
            'rent_out_id' => $cheque->rent_out_id,
            'date' => $journalDate ?? $cheque->date->format('Y-m-d'),
            'credit' => $payAmount,
            'debit' => 0,
            'account_id' => $paymentMethodId,
            'source' => 'PaymentTerm',
            'source_id' => $term->id,
            'model' => 'RentOutCheque',
            'model_id' => $cheque->id,
            'due_date' => $term->due_date?->format('Y-m-d'),
            'paid_date' => $journalDate ?? $cheque->date->format('Y-m-d'),
            'cheque_date' => $cheque->date->format('Y-m-d'),
            'cheque_no' => $cheque->cheque_no,
            'bank_name' => $cheque->bank_name,
            'reason' => 'Cheque #'.($cheque->cheque_no ?? '').' cleared',
            'group' => 'Rent Payment',
            'category' => $term->label ?? '',
            'payment_type' => 'Cheque',
            'remark' => $remark ?: ('Cheque #'.($cheque->cheque_no ?? '').' cleared'),
            'created_by' => auth()->id(),
        ];

        return (new StorePaymentAction())->execute($data);
    }
}
