<?php

namespace App\Console\Commands\SingleUse;

use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AddMissingInventoryCostJournalEntriesCommand extends Command
{
    protected $signature = 'app:add-missing-inventory-cost-journal-entries
                            {--dry-run : Show what would be done without making changes}
                            {--sale-id= : Process only a specific sale ID}';

    protected $description = 'Add missing Cost of Goods Sold journal entries for sales where inventory cost was 0 at the time of sale';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $saleId = $this->option('sale-id');

        $this->info('Finding sales with missing Cost of Goods Sold journal entries...');

        // Get account IDs
        $accounts = Cache::get('accounts_slug_id_map', []);

        if (empty($accounts['inventory']) || empty($accounts['cost_of_goods_sold'])) {
            $this->error('Required accounts (Inventory, Cost of Goods Sold) not found in database.');

            return Command::FAILURE;
        }

        $cogsAccountId = $accounts['cost_of_goods_sold'];
        $inventoryAccountId = $accounts['inventory'];

        // Find sales with journals but missing COGS entries
        $query = Sale::with(['journal.entries', 'items.product', 'items.inventory'])
            ->whereHas('journal')
            ->where('status', 'completed');

        if ($saleId) {
            $query->where('id', $saleId);
        }

        $sales = $query->get();

        $this->info("Found {$sales->count()} sales with journal entries.");

        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $totalCostAdded = 0;

        foreach ($sales as $sale) {
            try {
                $journal = $sale->journal;

                if (! $journal) {
                    $this->warn("Sale #{$sale->id} ({$sale->invoice_no}) has no journal. Skipping.");
                    $skippedCount++;

                    continue;
                }

                // Check if COGS entry already exists
                $hasCogsEntry = $journal
                    ->entries()
                    ->where(function ($query) use ($cogsAccountId) {
                        $query->where('account_id', $cogsAccountId)->orWhere('counter_account_id', $cogsAccountId);
                    })
                    ->exists();

                if ($hasCogsEntry) {
                    $this->line("Sale #{$sale->id} ({$sale->invoice_no}) already has COGS entry. Skipping.");
                    $skippedCount++;

                    continue;
                }

                // Calculate total cost using current inventory costs
                $totalCost = $sale
                    ->items()
                    ->with('product', 'inventory')
                    ->get()
                    ->filter(fn ($item) => $item->product?->type === 'product')
                    ->sum(function ($item) {
                        // Use current inventory cost if available, otherwise use product cost
                        $cost = $item->inventory?->cost ?? ($item->product?->cost ?? 0);

                        return $cost * $item->quantity;
                    });

                if ($totalCost <= 0) {
                    $this->warn("Sale #{$sale->id} ({$sale->invoice_no}) has no cost to add (totalCost: {$totalCost}). Skipping.");
                    $skippedCount++;

                    continue;
                }

                if ($dryRun) {
                    $this->info("Would add COGS entry for Sale #{$sale->id} ({$sale->invoice_no}): Cost = ".number_format($totalCost, 2));
                    $processedCount++;
                    $totalCostAdded += $totalCost;

                    continue;
                }

                // Create journal entries
                $remarks = 'Cost of goods sold (Inventory transfer) - Added retroactively';
                $entries = $this->makeEntryPair($cogsAccountId, $inventoryAccountId, $totalCost, 0, $remarks, $journal, $sale);

                // Insert entries
                JournalEntry::insert($entries);

                $this->info("Added COGS entry for Sale #{$sale->id} ({$sale->invoice_no}): Cost = ".number_format($totalCost, 2));
                $processedCount++;
                $totalCostAdded += $totalCost;
            } catch (\Throwable $th) {
                $this->error("Error processing Sale #{$sale->id} ({$sale->invoice_no}): ".$th->getMessage());
                $errorCount++;
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Processed: {$processedCount}");
        $this->info("Skipped: {$skippedCount}");
        $this->info("Errors: {$errorCount}");
        if ($totalCostAdded > 0) {
            $this->info('Total Cost Added: '.number_format($totalCostAdded, 2));
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes were made. Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }

    protected function makeEntryPair($accountId1, $accountId2, $debit, $credit, $remarks, $journal, $sale)
    {
        $base = [
            'journal_id' => $journal->id,
            'branch_id' => $journal->branch_id,
            'date' => $journal->date,
            'source' => $journal->source,
            'person_name' => $journal->person_name,
            'description' => $journal->description,
            'journal_remarks' => $journal->remarks,
            'reference_number' => $journal->reference_number,
            'model' => null,
            'model_id' => null,
            'created_by' => $sale->created_by,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return [
            array_merge($base, [
                'account_id' => $accountId1,
                'counter_account_id' => $accountId2,
                'debit' => $debit,
                'credit' => $credit,
                'remarks' => $remarks,
            ]),
            array_merge($base, [
                'account_id' => $accountId2,
                'counter_account_id' => $accountId1,
                'debit' => $credit,
                'credit' => $debit,
                'remarks' => $remarks,
            ]),
        ];
    }
}
