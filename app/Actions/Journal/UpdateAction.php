<?php

namespace App\Actions\Journal;

use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Journal::find($id);
            if (! $model) {
                throw new \Exception("Journal not found with the specified ID: $id.", 1);
            }

            validationHelper(Journal::rules($id), $data);
            $model->update($data);

            if (true) {
                // Get entry IDs before deletion
                $entryIds = $model->entries()->pluck('id')->toArray();

                // Delete pivot table relationships for journal_entry_counter_accounts
                if (! empty($entryIds)) {
                    DB::table('journal_entry_counter_accounts')->where('tenant_id', $model->tenant_id)->whereIn('journal_entry_id', $entryIds)->delete();
                }
            }

            // Delete existing entries
            $model->entries()->delete();

            // Create new entries
            $entries = [];
            foreach ($data['entries'] as $value) {
                $single = $value;

                $single['date'] = $model->date;
                $single['branch_id'] = $model->branch_id;
                $single['source'] = $model->source;
                $single['person_name'] = $value['person_name'] ?? $model->person_name;
                $single['description'] = $value['description'] ?? $model->description;
                $single['journal_remarks'] = $model->remarks;
                $single['reference_number'] = $model->reference_number;

                $single['model'] = $single['model'] ?? null;
                $single['model_id'] = $single['model_id'] ?? null;
                $single['journal_id'] = $model->id;
                $single['created_at'] = now();
                $single['updated_at'] = now();
                $entries[] = $single;
            }
            if ($entries) {
                JournalEntry::insert($entries);
            }

            // Sync counter accounts to pivot table
            (new SyncCounterAccountsAction())->execute($model->id);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Journal';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
