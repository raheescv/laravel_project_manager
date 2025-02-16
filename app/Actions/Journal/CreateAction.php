<?php

namespace App\Actions\Journal;

use App\Models\Journal;
use App\Models\JournalEntry;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(Journal::rules(), $data);
            $model = Journal::create($data);
            $entries = [];
            foreach ($data['entries'] as $value) {
                $single = $value;
                $single['model'] = $single['model'] ?? null;
                $single['model_id'] = $single['model_id'] ?? null;
                $single['journal_id'] = $model->id;
                $single['created_at'] = now();
                $entries[] = $single;
            }
            if ($entries) {
                JournalEntry::insert($entries);
            }
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
