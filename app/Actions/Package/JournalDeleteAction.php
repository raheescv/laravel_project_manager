<?php

namespace App\Actions\Package;

use App\Models\JournalEntry;
use Exception;

class JournalDeleteAction
{
    public function execute($model, $userId)
    {
        try {
            $model->journals()->each(function ($journal) use ($userId): void {
                $journal->entries()->update(['deleted_by' => $userId]);
                $journal->entriesCounterAccounts()->delete();
                $journal->entries()->delete();
                $journal->delete();
            });
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted The Journal Entries';
            $return['data'] = [];
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    public function executeByEntryId($id, $userId)
    {
        try {
            $journalEntry = JournalEntry::where('model', 'PackagePayment')->where('model_id', $id)->first();
            if (! $journalEntry) {
                throw new Exception("Journal Entry not found with the specified ID: $id.", 1);
            }
            $journal = $journalEntry->journal;

            $journal->entries()->update(['deleted_by' => $userId]);
            $journal->entriesCounterAccounts()->delete();
            $journal->entries()->delete();
            $journal->delete();

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted The Journal Entry';
            $return['data'] = [];
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
