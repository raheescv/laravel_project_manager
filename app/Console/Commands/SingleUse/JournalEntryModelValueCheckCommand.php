<?php

namespace App\Console\Commands\SingleUse;

use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Console\Command;

class JournalEntryModelValueCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:journal-entry-model-value-check-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update journal entry model and model_id based on description invoice number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update journal entries...');

        // Get all journal entries where both model and model_id are null
        $journalEntries = JournalEntry::whereNull('model')
            ->whereNull('model_id')
            ->whereNotNull('description')
            ->get();

        $this->info("Found {$journalEntries->count()} journal entries to process.");

        $updated = 0;
        $failed = 0;

        foreach ($journalEntries as $entry) {
            if (empty($entry->description)) {
                continue;
            }

            // Parse description: "Sale:INV-HM-25/26-5350" or "Purchase:invoice_no" etc.
            if (!str_contains($entry->description, ':')) {
                continue;
            }

            [$modelType, $identifier] = explode(':', $entry->description, 2);

            if (empty($modelType) || empty($identifier)) {
                continue;
            }

            $model = null;
            $modelId = null;

            try {
                switch ($modelType) {
                    case 'Sale':
                        $model = Sale::where('invoice_no', $identifier)->first();
                        if ($model) {
                            $modelId = $model->id;
                        }
                        break;

                    case 'Purchase':
                        $model = Purchase::where('invoice_no', $identifier)->first();
                        if ($model) {
                            $modelId = $model->id;
                        }
                        break;

                    case 'PurchaseReturn':
                        $model = PurchaseReturn::where('invoice_no', $identifier)->first();
                        if ($model) {
                            $modelId = $model->id;
                        }
                        break;

                    case 'SaleReturn':
                        // SaleReturn uses ID instead of invoice_no
                        $model = SaleReturn::find($identifier);
                        if ($model) {
                            $modelId = $model->id;
                        }
                        break;

                    default:
                        $this->warn("Unknown model type: {$modelType} for entry ID: {$entry->id}");
                        $failed++;
                        continue 2;
                }

                if ($model && $modelId) {
                    $entry->update([
                        'model' => $modelType,
                        'model_id' => $modelId,
                    ]);
                    $updated++;
                    $this->info("Updated entry ID {$entry->id}: {$modelType} with ID {$modelId}");
                } else {
                    $this->warn("Could not find {$modelType} with identifier: {$identifier} for entry ID: {$entry->id}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("Error processing entry ID {$entry->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("\nCompleted!");
        $this->info("Updated: {$updated}");
        $this->info("Failed: {$failed}");
    }
}
