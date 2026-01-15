<?php

namespace App\Actions\Journal;

use App\Models\Journal;
use App\Models\JournalEntry;

class SyncCounterAccountsAction
{
    /**
     * Sync multiple counter accounts to pivot table for journal entries
     */
    public function execute($journalId): void
    {
        if (! $journalId) {
            return;
        }

        $journal = Journal::find($journalId);
        if (! $journal) {
            return;
        }

        $entries = JournalEntry::where('journal_id', $journalId)->get();

        if ($entries->isEmpty()) {
            return;
        }

        foreach ($entries as $entry) {
            $counterAccountsData = [];

            // Find all other entries with opposite debit/credit as counter accounts
            foreach ($entries as $otherEntry) {
                if ($entry->id === $otherEntry->id) {
                    continue;
                }

                // Check if this is a counter account (opposite transaction type)
                if (($entry->debit > 0 && $otherEntry->credit > 0) ||
                    ($entry->credit > 0 && $otherEntry->debit > 0)) {
                    // Include tenant_id, journal_id, and branch_id in pivot data
                    $counterAccountsData[$otherEntry->account_id] = [
                        'tenant_id' => $entry->tenant_id,
                        'journal_id' => $journal->id,
                        'branch_id' => $entry->branch_id,
                    ];
                }
            }

            // Sync counter accounts to pivot table (removes old and adds new)
            // Format: [account_id => ['tenant_id' => value], ...]
            $entry->counterAccounts()->sync($counterAccountsData);
        }
    }
}
