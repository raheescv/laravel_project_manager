<?php

namespace App\Actions\Sale;

use Illuminate\Support\Facades\DB;

class JournalDeleteAction
{
    public function execute($model, $user_id)
    {
        try {
            $model->journals()->each(function ($journal) use ($user_id): void {
                $entryIds = $journal->entries()->pluck('id')->toArray();

                if (! empty($entryIds)) {
                    DB::table('journal_entry_counter_accounts')
                        ->where('tenant_id', $journal->tenant_id)
                        ->whereIn('journal_entry_id', $entryIds)
                        ->delete();
                }

                $journal->entries()->update(['deleted_by' => $user_id]);
                $journal->entries()->delete();
                $journal->delete();
            });
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted The Journal Entries';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
