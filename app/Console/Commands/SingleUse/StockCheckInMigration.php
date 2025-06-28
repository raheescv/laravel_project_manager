<?php

namespace App\Console\Commands\SingleUse;

use App\Actions\Product\Inventory\UpdateAction;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StockCheckInMigration extends Command
{
    protected $signature = 'app:stock-check-in-migration';

    protected $description = 'Compare stock quantities between two databases and generate a comparison report';

    public function handle()
    {
        $this->info('Starting stock comparison between databases...');

        try {
            // Test database connections
            $this->testDatabaseConnections();

            // Get comparison data
            $comparisonData = $this->getStockComparison();

            // Generate report
            $this->generateReport($comparisonData);

            $this->info('Stock comparison completed successfully!');

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }

        return 0;
    }

    private function testDatabaseConnections()
    {
        $this->info('Testing database connections...');

        try {
            // Test primary database
            DB::connection('mysql')->getPdo();
            $this->info('✓ Primary database (mysql) connection successful');

            // Test secondary database
            DB::connection('mysql2')->getPdo();
            $this->info('✓ Secondary database (mysql2) connection successful');

        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: '.$e->getMessage());
        }
    }

    /**
     * Get stock comparison data between both databases
     */
    private function getStockComparison()
    {
        $this->info('Fetching stock data from both databases...');

        // Get stock data from secondary database (mysql2)
        $secondaryStockQuery = DB::connection('mysql2')
            ->table('stocks as s')
            ->join('products as p', 's.product_id', '=', 'p.id')
            ->select(
                's.product_id',
                'p.name as product_name',
                'p.code as product_code',
                's.quantity as stock_quantity',
                's.branch_id'
            )
            ->whereNull('s.deleted_at')
            ->whereNull('p.deleted_at');
        $secondaryStock = $secondaryStockQuery->get()->keyBy(function ($item) {
            return $item->product_id.'_'.$item->branch_id;
        });

        $this->info('Found '.$secondaryStock->count().' stock records in secondary database');

        // Get inventory data from primary database (mysql)
        $primaryInventoryQuery = Inventory::join('products', 'inventories.product_id', '=', 'products.id')
            ->select(
                'inventories.id',
                'inventories.product_id',
                'products.name as product_name',
                'products.code as product_code',
                'products.second_reference_no',
                'inventories.quantity as inventory_quantity',
                'inventories.branch_id'
            )
            ->whereNotNull('products.second_reference_no');

        $primaryInventory = $primaryInventoryQuery->get()->keyBy(function ($item) {
            return $item->second_reference_no.'_'.$item->branch_id;
        });

        $this->info('Found '.$primaryInventory->count().' inventory records in primary database');

        // Compare the data
        return $this->compareStockData($secondaryStock, $primaryInventory);
    }

    /**
     * Compare stock data from both databases
     */
    private function compareStockData($secondaryStock, $primaryInventory)
    {
        $mismatches = collect();
        $matches = collect();

        $progressBar = $this->output->createProgressBar($secondaryStock->count());
        $progressBar->start();

        // Check each secondary stock record
        foreach ($secondaryStock as $key => $secondaryItem) {
            $progressBar->advance();

            $primaryKey = $secondaryItem->product_id.'_'.$secondaryItem->branch_id;

            $primaryItem = $primaryInventory->get($primaryKey);

            if (abs($secondaryItem->stock_quantity - $primaryItem->inventory_quantity) > 0) {
                // Quantities don't match
                $mismatches->push([
                    'product_id_secondary' => $secondaryItem->product_id,
                    'product_id_primary' => $primaryItem->product_id,
                    'id' => $primaryItem->id,
                    'product_name' => $secondaryItem->product_name,
                    'product_code' => $secondaryItem->product_code,
                    'branch_id' => $secondaryItem->branch_id,
                    'secondary_quantity' => $secondaryItem->stock_quantity,
                    'primary_quantity' => $primaryItem->inventory_quantity,
                    'difference' => $secondaryItem->stock_quantity - $primaryItem->inventory_quantity,
                ]);
            } else {
                // Quantities match
                // $matches->push([
                //     'product_id_secondary' => $secondaryItem->product_id,
                //     'product_id_primary' => $primaryItem->product_id,
                //     'product_name' => $secondaryItem->product_name,
                //     'product_code' => $secondaryItem->product_code,
                //     'branch_id' => $secondaryItem->branch_id,
                //     'quantity' => $secondaryItem->stock_quantity,
                // ]);
            }
        }
        $progressBar->finish();
        $this->newLine();

        return [
            'matches' => $matches,
            'mismatches' => $mismatches,
        ];
    }

    /**
     * Generate and display the comparison report
     */
    private function generateReport($comparisonData)
    {
        $this->newLine();
        $this->info('=== STOCK COMPARISON REPORT ===');
        $this->newLine();

        // Summary
        $this->info('SUMMARY:');
        $this->info('Matching quantities: '.$comparisonData['matches']->count());
        $this->info('Mismatched quantities: '.$comparisonData['mismatches']->count());
        $this->newLine();

        // Mismatches
        if ($comparisonData['mismatches']->isNotEmpty()) {
            $this->error('QUANTITY MISMATCHES:');

            $headers = ['Product Code', 'Product Name', 'Branch', 'Secondary DB', 'Primary DB', 'Difference'];
            $rows = $comparisonData['mismatches']->map(function ($item) {
                return [
                    $item['product_code'],
                    substr($item['product_name'], 0, 30),
                    $item['branch_id'],
                    number_format($item['secondary_quantity'], 3),
                    number_format($item['primary_quantity'], 3),
                    number_format($item['difference'], 3),
                ];
            })->toArray();

            $this->table($headers, $rows);
            $this->newLine();
        }
        if ($this->confirm('do you want to take create product log quantity?', false)) {
            $this->productCreatedLog();
        }
        if ($this->confirm('do you want adjustment quantity?', false)) {
            $this->stockAdjustment($comparisonData);
        }
    }

    public function productCreatedLog()
    {
        $secondaryStockLogQuery = DB::connection('mysql2')
            ->table('stock_logs')
            ->select(
                'product_id',
                'quantity_in',
                'branch_id'
            )
            ->whereNotIn('stock_logs.branch_id', [3])
            ->where('stock_logs.quantity_in', '!=', 0)
            ->get();
        $progressBar = $this->output->createProgressBar(count($secondaryStockLogQuery));
        $progressBar->start();
        foreach ($secondaryStockLogQuery as $key => $value) {
            $progressBar->advance();
            $product = Product::where('second_reference_no', $value->product_id)->first();
            if ($product) {
                $inventory = Inventory::where('product_id', $product->id)->where('branch_id', $value->branch_id)->first();
                if ($inventory) {
                    $inventory = $inventory->toArray();
                    $inventory['quantity'] += $value->quantity_in;
                    $inventory['remarks'] = 'Product Created';
                    $inventory['model'] = null;
                    $inventory['model_id'] = null;
                    $inventory['updated_by'] = 1;
                    $response = (new UpdateAction())->execute($inventory, $inventory['id']);
                    if (! $response['success']) {
                        throw new \Exception($response['message'], 1);
                    }
                }
            }
        }
        $progressBar->finish();
    }

    public function stockAdjustment($comparisonData)
    {
        $progressBar = $this->output->createProgressBar(count($comparisonData['mismatches']));
        $progressBar->start();
        foreach ($comparisonData['mismatches'] as $key => $value) {
            $progressBar->advance();
            $inventory = Inventory::find($value['id'])->toArray();
            $inventory['quantity'] += $value['difference'];
            $inventory['remarks'] = 'Stock Adjustment';
            $inventory['model'] = null;
            $inventory['model_id'] = null;
            $inventory['updated_by'] = 1;
            $response = (new UpdateAction())->execute($inventory, $inventory['id']);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
        }
        $progressBar->finish();
    }
}
