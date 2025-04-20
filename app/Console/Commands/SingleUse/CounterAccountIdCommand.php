<?php

namespace App\Console\Commands\SingleUse;

use App\Models\JournalEntry;
use Illuminate\Console\Command;

class CounterAccountIdCommand extends Command
{
    protected $signature = 'app:counter-account-id-command';

    protected $description = 'Update counter_account_id for journal entries where counter_account_id = 0';

    public function handle()
    {
        $entries = JournalEntry::where('counter_account_id', 0)
            // ->where('journal_id', 1)
            ->groupBy('journal_id', 'remarks')
            ->select('journal_id')
            ->selectRaw('GROUP_CONCAT(id) as ids')
            ->get();

        foreach ($entries as $entry) {
            try {
                $ids = explode(',', $entry->ids);

                if (count($ids) != 2) {
                    $this->warn("Less than two journal entries found for group: {$entry->ids}");

                    continue;
                }

                $first = JournalEntry::find($ids[0]);
                $second = JournalEntry::find($ids[1]);

                if (! $first || ! $second) {
                    $this->warn("Journal entries not found for IDs: {$entry->ids}");

                    continue;
                }

                $first->update(['counter_account_id' => $second->account_id]);
                $second->update(['counter_account_id' => $first->account_id]);

                $this->info("Updated pair: {$first->id} <=> {$second->id}");
            } catch (\Throwable $th) {
                $this->error("Error processing IDs: {$entry->ids}. Message: ".$th->getMessage());
            }
        }

        $this->info('Counter account IDs updated successfully.');
    }
}
