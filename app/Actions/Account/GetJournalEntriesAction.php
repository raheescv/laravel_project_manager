<?php

namespace App\Actions\Account;

use App\Models\Journal;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetJournalEntriesAction
{
    public function execute(int $journalId): array
    {
        try {
            $journal = $this->getJournal($journalId);
            $journalData = $this->mapJournalToArray($journal);
            $entries = $this->mapEntriesToArray($journal->entries);

            return [
                'success' => true,
                'journal' => $journalData,
                'entries' => $entries,
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Journal not found',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to load journal entries: '.$e->getMessage(),
            ];
        }
    }

    private function getJournal(int $journalId): Journal
    {
        return Journal::with('entries.account')->findOrFail($journalId);
    }

    private function mapJournalToArray(Journal $journal): array
    {
        // Get the first entry's journal_remarks if available
        $firstEntry = $journal->entries->first();
        $journalRemarks = $firstEntry ? $firstEntry->journal_remarks : null;

        return [
            'id' => $journal->id,
            'description' => $journal->description,
            'remarks' => $journal->remarks,
            'journal_remarks' => $journalRemarks,
            'date' => $journal->date,
        ];
    }

    private function mapEntriesToArray($entries): array
    {
        return $entries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'date' => $entry->date,
                'account_id' => $entry->account_id,
                'account' => $entry->account ? [
                    'id' => $entry->account->id,
                    'name' => $entry->account->name,
                ] : null,
                'description' => $entry->description,
                'journal_remarks' => $entry->journal_remarks,
                'reference_number' => $entry->reference_number,
                'debit' => (float) $entry->debit,
                'credit' => (float) $entry->credit,
            ];
        })->toArray();
    }
}
