<?php

namespace App\Actions\Student;

use App\Models\Account;
use App\Models\JournalEntry;

/**
 * A student's card balance: the student account's own ledger, credit minus debit,
 * across every branch, plus any opening balance carried in on the account.
 *
 * Positive means prepaid money is available; negative means the card is in
 * overdraft. There is no stored balance to drift — top-ups, purchases, returns
 * and refunds all reach the card only through their journals.
 *
 * Read-only, so it returns the value directly rather than the action envelope.
 */
class GetBalanceAction
{
    public function execute(int $accountId): float
    {
        return $this->many([$accountId])[$accountId] ?? 0.0;
    }

    /**
     * Balances for several student accounts in two queries.
     *
     * @param  array<int, int>  $accountIds
     * @return array<int, float> keyed by account id
     */
    public function many(array $accountIds): array
    {
        $accountIds = array_values(array_unique(array_map('intval', $accountIds)));
        if (! $accountIds) {
            return [];
        }

        // JournalEntry carries the tenant scope but no branch scope: a card spends
        // the same money in every canteen, whichever branches the viewer is assigned.
        $ledger = JournalEntry::query()
            ->whereIn('account_id', $accountIds)
            ->groupBy('account_id')
            ->selectRaw('account_id, COALESCE(SUM(credit), 0) - COALESCE(SUM(debit), 0) as balance')
            ->pluck('balance', 'account_id');

        $opening = Account::query()
            ->whereIn('id', $accountIds)
            ->get(['id', 'opening_debit', 'opening_credit'])
            ->mapWithKeys(fn (Account $account) => [$account->id => (float) $account->opening_credit - (float) $account->opening_debit]);

        $balances = [];
        foreach ($accountIds as $id) {
            $balances[$id] = round((float) ($ledger[$id] ?? 0) + (float) ($opening[$id] ?? 0), 2);
        }

        return $balances;
    }
}
