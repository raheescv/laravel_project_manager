<?php

namespace App\Actions\Package;

use App\Models\JournalEntry;
use Exception;
use Illuminate\Support\Facades\DB;

class JournalDeleteAction
{
    public function execute($model, $userId)
    {
        try {
            $model->journals()->each(function ($journal) use ($userId): void {
                $entryIds = $journal->entries()->pluck('id')->toArray();

                if (! empty($entryIds)) {
                    DB::table('journal_entry_counter_accounts')
                        ->where('tenant_id', $journal->tenant_id)
                        ->whereIn('journal_entry_id', $entryIds)
                        ->delete();
                }

                $journal->entries()->update(['deleted_by' => $userId]);
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
            $entryIds = $journal->entries()->pluck('id')->toArray();

            if (! empty($entryIds)) {
                DB::table('journal_entry_counter_accounts')
                    ->where('tenant_id', $journal->tenant_id)
                    ->whereIn('journal_entry_id', $journalEntry->id)
                    ->delete();
            }
            $journal->entries()->update(['deleted_by' => $userId]);
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
