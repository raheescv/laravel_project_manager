<?php

namespace App\Console\Commands\SingleUse;

use App\Actions\Sale\JournalDeleteAction;
use App\Actions\Sale\JournalEntryAction;
use App\Models\Sale;
use Exception;
use Illuminate\Console\Command;

class RewriteSaleJournalEntriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rewrite-sale-journal-entries-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sales = Sale::completed()
        // ->limit(1)
        ->get();

        $this->info("Processing {$sales->count()} sales...");

        $this->withProgressBar($sales, function ($sale) {
            // $this->info("Processing sale {$sale->id}...");
            $response = (new JournalDeleteAction())->execute($sale, $sale->created_by);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            $response = (new JournalEntryAction())->execute($sale, $sale->created_by);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        });

        $this->newLine();
        $this->info('Completed processing all sales.');
    }
}
