<?php

namespace App\Actions\RentOut;

use App\Actions\RentOut\Payment\TransferTransactionAction;
use App\Models\RentOut;
use App\Models\RentOutTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * The rows of a rent-out (rent) statement: rent falling due as debits, rent
 * received as credits, and payment transfers both ways — a Transfer Out is a
 * debit (the money left this agreement), a Transfer In a credit. Utility,
 * service, payout and ROI rows have their own statements and stay out.
 */
class StatementEntriesAction
{
    /**
     * @return Collection<int, array{date: mixed, order: int, payment_mode: string, cheque_no: string, debit: float, credit: float, remark: string}>
     */
    public function execute(RentOut $rentOut, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $entries = collect();

        foreach ($rentOut->paymentTerms as $term) {
            $entries->push([
                'date' => $term->due_date,
                'order' => 0,
                'payment_mode' => 'Rent Due',
                'cheque_no' => '',
                'debit' => (float) ($term->total ?? 0),
                'credit' => 0.0,
                'remark' => $term->remarks ?? '',
            ]);
        }

        $transactions = RentOutTransaction::with('account')
            ->where('rent_out_id', $rentOut->id)
            ->where(function ($q) {
                $q->where('source', 'PaymentTerm')
                    ->orWhere('source', TransferTransactionAction::SOURCE)
                    ->orWhere(fn ($q) => $q->where('source', 'Payment')->where(fn ($q) => $q->whereNull('group')->orWhere('group', '!=', 'Utility Payment')));
            })
            ->get();

        foreach ($transactions as $transaction) {
            $isTransfer = $transaction->source === TransferTransactionAction::SOURCE
                && $transaction->payment_type === TransferTransactionAction::PAYMENT_TYPE;

            $entries->push([
                'date' => $transaction->date,
                'order' => 1,
                'payment_mode' => $isTransfer
                    ? ($transaction->debit > 0 ? 'Transfer Out' : 'Transfer In')
                    : ($transaction->account?->name ?? $transaction->payment_type ?? ''),
                'cheque_no' => $transaction->cheque_no ?? '',
                'debit' => (float) $transaction->debit,
                'credit' => (float) $transaction->credit,
                'remark' => $transaction->remark ?? '',
            ]);
        }

        if ($fromDate && $toDate) {
            $entries = $entries->filter(fn (array $entry): bool => Carbon::parse($entry['date'])->betweenIncluded(Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()));
        }

        // Chronological; on the same day the amount due comes before what paid it.
        return $entries->sortBy(fn (array $entry): string => Carbon::parse($entry['date'])->format('Y-m-d').'-'.$entry['order'])->values();
    }
}
