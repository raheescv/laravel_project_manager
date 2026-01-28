<?php

namespace App\Actions\Journal;

use App\Models\Journal;
use App\Models\JournalEntry;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            validationHelper(Journal::rules(), $data);
            $model = Journal::create($data);
            $entries = [];
            foreach ($data['entries'] as $value) {
                $single = $value;

                $single['tenant_id'] = $model->tenant_id;
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
            // (new SyncCounterAccountsAction())->execute($model->id);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Journal';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
