<?php

namespace App\Actions\Sale;

class JournalDeleteAction
{
    public function execute($model, $user_id)
    {
        try {
            $model->journals()->each(function ($journal) use ($user_id): void {
                $journal->entriesCounterAccounts()->delete();
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
