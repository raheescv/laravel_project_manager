<?php

namespace App\Console\Commands;

use App\Models\JournalEntry;
use Illuminate\Console\Command;

class SingleUseJournalDateMigrationCommand extends Command
{
    protected $signature = 'app:single-use-journal-date-migration-command';

    protected $description = 'Command description';

    public function handle()
    {
        $list = JournalEntry::with('journal')->whereNull('branch_id')->get();
        $this->withProgressBar($list, function ($model) {
            $model->branch_id = $model->journal->branch_id;
            $model->date = $model->journal->date;
            $model->source = $model->journal->source;

            $model->person_name = $model->journal->person_name;
            $model->description = $model->journal->description;
            $model->journal_remarks = $model->journal->remarks;
            $model->reference_number = $model->journal->reference_number;

            $model->journal_model = $model->journal->model;
            $model->journal_model_id = $model->journal->model_id;

            $model->save();
        });

        $this->newLine();
        $this->info('Journal entry dates migrated successfully.');
    }
}
