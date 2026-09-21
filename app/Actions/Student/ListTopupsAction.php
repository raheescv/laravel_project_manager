<?php

namespace App\Actions\Student;

use App\Models\JournalEntry;
use App\Models\QpayTransaction;

/**
 * Everything that put money on a student's card or took it off outside a
 * purchase: office entries (ManualEntryAction) and online payments/refunds —
 * debit card through QPay, credit card through the Mastercard Gateway — newest first.
 *
 * Office entries are read from the ledger — the journal IS the record, there is
 * no second table — while online rows come from qpay_transactions so a pending,
 * failed or under-review payment (which has no journal) is still listed.
 *
 * Read-only, so it returns the rows directly.
 *
 * @return array<int, array>
 */
class ListTopupsAction
{
    public function execute(int $accountId, int $limit = 50): array
    {
        $manual = JournalEntry::query()
            ->with(['counterAccount:id,name', 'journal:id,created_by', 'journal.createdBy:id,name'])
            ->where('account_id', $accountId)
            ->whereIn('source', ['student_topup', 'student_topup_refund'])
            ->where(fn ($q) => $q->whereNull('model')->orWhere('model', '!=', 'QpayTransaction'))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (JournalEntry $entry) => [
                'at' => $entry->created_at,
                'date' => $entry->date,
                'channel' => 'Office',
                'method' => $entry->counterAccount?->name ?? '-',
                'reference' => null,
                'amount' => round((float) $entry->credit - (float) $entry->debit, 2),
                'status' => 'success',
                'status_label' => 'Recorded',
                'note' => $entry->remarks,
                'by' => $entry->journal?->createdBy?->name,
                'qpay' => null,
            ]);

        $online = QpayTransaction::with('guardian:id,name')
            ->where('account_id', $accountId)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (QpayTransaction $transaction) => [
                'at' => $transaction->created_at,
                'date' => $transaction->created_at?->toDateString(),
                'channel' => $transaction->methodLabel(),
                'method' => $transaction->gatewayLabel().' · '.($transaction->cardLabel() ?: ucfirst($transaction->type)),
                'reference' => $transaction->pun,
                'amount' => round((float) $transaction->amount * ($transaction->type === QpayTransaction::TYPE_REFUND ? -1 : 1), 2),
                'status' => $transaction->status,
                'status_label' => $transaction->statusLabel(),
                'note' => $transaction->failure_reason ?: trim($transaction->gateway_status.' '.$transaction->gateway_status_message),
                'by' => $transaction->guardian?->name,
                'qpay' => $transaction,
            ]);

        return $manual->concat($online)
            ->sortByDesc(fn (array $row) => $row['at'])
            ->take($limit)
            ->values()
            ->all();
    }
}
