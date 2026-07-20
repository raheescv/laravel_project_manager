<?php

namespace App\Console\Commands\SingleUse;

use App\Actions\Appointment\CreateAction as AppointmentCreateAction;
use App\Actions\InventoryTransfer\CreateAction as InventoryTransferCreateAction;
use App\Actions\Purchase\CreateAction as PurchaseCreateAction;
use App\Actions\SaleReturn\CreateAction as SaleReturnCreateAction;
use App\Jobs\BranchProductCreationJob;
use App\Jobs\MigrateSalesChunkJob;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\UserHasBranch;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class MigrateDataCommand extends Command
{
    protected $signature = 'migrate:database-data
        {--force : Run even if the target database already contains migrated data}
        {--fast : Also relax global sync_binlog for maximum speed (dedicated migration DB only; restored on finish)}';

    protected $description = 'Migrate data from mysql2 to mysql database';

    public $paymentModesIds;

    public $tenantId;

    public function handle()
    {
        // This command is not idempotent: branches() truncates and every other entity uses
        // ::create(), so a second run duplicates all data. Abort if the target already looks
        // migrated, unless explicitly forced.
        if (! $this->option('force') && (User::query()->exists() || Product::query()->exists() || Sale::query()->exists())) {
            $this->error('Target database already contains data (users/products/sales). This command is a one-shot migration and would duplicate records.');
            $this->warn('Start from a fresh, migrated (empty) database, or re-run with --force if you are certain.');

            return self::FAILURE;
        }

        $this->info('Starting data migration...');

        // Force the queue to run inline for the whole migration. products()/service()
        // dispatch BranchProductCreationJob -> BranchInventoryCreationJob, which create the
        // Inventory rows that sales()/purchases()/salesReturns()/stockTransfers() immediately
        // look up. On the database queue those jobs would still be pending, so the lookups
        // throw "Inventory not found" and silently skip the row. Running sync guarantees the
        // inventory exists before any transaction is replayed.
        config(['queue.default' => 'sync']);

        // branches/accounts are inserted with raw DB::table()->insertOrIgnore(), which bypasses
        // the BelongsToTenant creating-hook. Their tenant_id columns are NOT NULL with no default,
        // so tenant_id must be supplied explicitly or every row is silently dropped by insertOrIgnore.
        $this->tenantId = app(TenantService::class)->getCurrentTenantId();

        $this->paymentModesIds = DB::connection('mysql2')->table('account_heads')->whereIn('account_category_id', [16, 17])->pluck('id', 'id')->toArray();
        Artisan::call('db:ensure-procedures');

        // Speed: this migration replays ~100k+ sales/purchases, each in its own short transaction.
        // With the default innodb_flush_log_at_trx_commit=1 every COMMIT fsyncs the redo log to
        // disk, and that per-commit fsync — not CPU or query planning — is what dominates runtime.
        // Relaxing it to 2 (flush once per second) is the single biggest win. This variable is
        // GLOBAL-only in MySQL, so we capture the current value and restore it in the finally block
        // below; the relaxed setting is in effect only while this one-shot migration runs.
        $originalFlushLog = null;
        try {
            $originalFlushLog = DB::selectOne('SELECT @@GLOBAL.innodb_flush_log_at_trx_commit AS v')->v;
            DB::statement('SET GLOBAL innodb_flush_log_at_trx_commit = 2');
        } catch (\Throwable $e) {
            $originalFlushLog = null;
            $this->warn('Could not relax innodb_flush_log_at_trx_commit (needs SET GLOBAL privilege) — continuing without it: '.$e->getMessage());
        }

        // --fast additionally relaxes sync_binlog (the binary log's own per-commit fsync). This
        // further reduces crash durability server-wide for the duration, so it is opt-in and only
        // appropriate on a dedicated migration DB. The original value is captured here and restored
        // in the finally block below.
        $originalSyncBinlog = null;
        if ($this->option('fast')) {
            try {
                $originalSyncBinlog = DB::selectOne('SELECT @@GLOBAL.sync_binlog AS v')->v;
                DB::statement('SET GLOBAL sync_binlog = 0');
                $this->warn('Turbo mode: sync_binlog=0 set globally for the migration (reduced crash durability until it finishes).');
            } catch (\Throwable $e) {
                $originalSyncBinlog = null;
                $this->warn('Could not set global sync_binlog (needs privilege) — continuing without it: '.$e->getMessage());
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        try {
            $this->branches();
            $this->settings();
            $this->accounts();
            $this->customer();
            $this->vendor();
            $this->service();
            $this->products();
            $this->employees();
            $this->users();
            $this->employeeCommissions();
            // The journal-entry actions replayed by sales()/salesReturns()/purchases() read
            // account + branch ids from long-lived (1-year) caches that AppServiceProvider primes
            // at boot. On a freshly wiped/migrated DB those caches were primed empty, so rebuild
            // them now that branches and the seeded locked accounts exist — otherwise
            // JournalEntryAction fails with "Undefined array key 'sale'".
            $this->refreshLookupCaches();
            $this->purchases();
            $this->appointments();
            $this->stockTransfers();
            $this->sales();
            $this->salesReturns();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Restore the global durability settings we relaxed, whatever happened above.
            if ($originalFlushLog !== null) {
                try {
                    DB::statement('SET GLOBAL innodb_flush_log_at_trx_commit = '.(int) $originalFlushLog);
                } catch (\Throwable $e) {
                    $this->warn('Could not restore global innodb_flush_log_at_trx_commit to '.$originalFlushLog.': '.$e->getMessage());
                }
            }
            if ($originalSyncBinlog !== null) {
                try {
                    DB::statement('SET GLOBAL sync_binlog = '.(int) $originalSyncBinlog);
                } catch (\Throwable $e) {
                    $this->warn('Could not restore global sync_binlog to '.$originalSyncBinlog.': '.$e->getMessage());
                }
            }
        }

        $this->info('Data migration completed successfully!');
    }

    private function refreshLookupCaches(): void
    {
        Cache::put('branches', Branch::select('id', 'name')->get(), now()->addYear());

        $accountSlugMap = DB::table('accounts')->where('is_locked', 1)->pluck('id', 'slug')->toArray();
        Cache::put('accounts_slug_id_map', $accountSlugMap, now()->addYear());

        if (! isset($accountSlugMap['sale'])) {
            $this->warn('Locked default accounts (slug "sale", "inventory", ...) are missing. Seed the default chart of accounts before running this command, or completed sales will fail their journal entries.');
        }
    }

    private function stockTransfers()
    {
        $this->info('Migrating stockTransfers...');
        $stockTransferCount = DB::connection('mysql2')->table('stock_transfers')->count();
        $progressBar = $this->output->createProgressBar($stockTransferCount);
        $progressBar->start();

        $stockTransfers = DB::connection('mysql2')
            ->table('stock_transfers')
            ->orderBy('id')
            ->get();
        foreach ($stockTransfers as $stockTransfer) {
            DB::beginTransaction();
            $progressBar->advance();
            try {
                $status = $stockTransfer->status == 1 ? 'pending' : 'completed';
                $created_by = User::where('type', 'user')->where('second_reference_no', $stockTransfer->created_by)->value('id');
                $approved_by = User::where('type', 'user')->where('second_reference_no', $stockTransfer->approved_by)->value('id');
                $updated_by = User::where('type', 'user')->where('second_reference_no', $stockTransfer->updated_by)->value('id');
                $data = [
                    'date' => $stockTransfer->date,
                    'branch_id' => $stockTransfer->branch_id,
                    'from_branch_id' => $stockTransfer->from_branch_id,
                    'to_branch_id' => $stockTransfer->to_branch_id,
                    'description' => $stockTransfer->description,
                    'status' => $status,
                    'created_by' => $created_by,
                    'approved_by' => $approved_by,
                    'approved_at' => $stockTransfer->updated_at,
                    'updated_by' => $updated_by,
                    'created_at' => $stockTransfer->created_at,
                    'updated_at' => $stockTransfer->updated_at,
                ];
                $data['items'] = [];
                $items = DB::connection('mysql2')->table('stock_transfer_items')->where('stock_transfer_id', $stockTransfer->id)->get();
                foreach ($items as $item) {
                    $product_id = Product::where('type', 'product')->where('second_reference_no', $item->product_id)->value('id');
                    $inventory_id = Inventory::where('product_id', $product_id)->value('id');
                    if (! $inventory_id) {
                        throw new \Exception('Inventory not found for product ID: '.$item->product_id);
                    }
                    $data['items'][] = [
                        'product_id' => $product_id,
                        'inventory_id' => $inventory_id,
                        'quantity' => $item->quantity,
                        'remark' => $item->remark,
                    ];
                }
                $groupedItems = [];
                foreach ($data['items'] as $item) {
                    if (isset($groupedItems[$item['inventory_id']])) {
                        $groupedItems[$item['inventory_id']]['quantity'] += $item['quantity'];
                    } else {
                        $groupedItems[$item['inventory_id']] = $item;
                    }
                }
                $data['items'] = array_values($groupedItems);
                $response = (new InventoryTransferCreateAction())->execute($data, 1);
                if (! $response['success']) {
                    $this->error('Failed to create Stock Transfer: '.$response['message']);
                    Log::error('Failed to create Stock Transfer: '.$response['message']);
                    Log::error($data);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                Log::error('Stock Transfer migration error: '.$e->getMessage());
            }
        }
        $progressBar->finish();
        $this->info('Stock transfers migration completed.');
    }

    private function branches()
    {
        $this->info('Migrating branches...');
        // Get total count for progress bar
        $branches = DB::connection('mysql2')->table('branches')->whereNull('deleted_at')->count();
        $progressBar = $this->output->createProgressBar($branches);
        $progressBar->start();
        $branches = DB::connection('mysql2')->table('branches')->whereNull('deleted_at')->get();
        DB::table('branches')->truncate();
        foreach ($branches as $branch) {
            $branchData = [
                'id' => $branch->id,
                'tenant_id' => $this->tenantId,
                'name' => $branch->name,
                'code' => $branch->code,
                'location' => $branch->code,
                'mobile' => '',
                'created_at' => $branch->created_at,
                'updated_at' => $branch->updated_at,
            ];
            DB::table('branches')->insertOrIgnore((array) $branchData);
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->info('Branches migration completed.');
    }

    private function settings()
    {
        $this->info('Migrating settings (configurations)...');

        // Old accounts DB stores settings as a key/value table: `configurations` (keys, values).
        // New project_manager DB uses `configurations` (tenant_id, key, value) unique per
        // (tenant_id, key). We map keys->key, values->value under tenant 1.
        $configurations = DB::connection('mysql2')
            ->table('configurations')
            ->get();

        $progressBar = $this->output->createProgressBar($configurations->count());
        $progressBar->start();

        foreach ($configurations as $configuration) {
            $progressBar->advance();

            // Preserve the new app's freshly-seeded theme_settings row; the old app's theme
            // structure is incompatible with the new UI and would break rendering.
            if ($configuration->keys === 'theme_settings') {
                continue;
            }

            // updateOrInsert on (tenant_id, key) keeps this idempotent and honours the unique
            // constraint. `value` is NOT NULL in the new schema, so coerce null -> ''.
            DB::table('configurations')->updateOrInsert(
                ['tenant_id' => 1, 'key' => $configuration->keys],
                [
                    'value' => $configuration->values ?? '',
                    'created_at' => $configuration->created_at,
                    'updated_at' => $configuration->updated_at,
                ]
            );
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Settings migration completed.');
    }

    private function appointments()
    {
        $this->info('Migrating appointments...');
        // Get total count for progress bar
        $totalTaskMasters = DB::connection('mysql2')
            ->table('task_masters')
            ->count();

        $progressBar = $this->output->createProgressBar($totalTaskMasters);
        $progressBar->start();

        DB::connection('mysql2')
            ->table('task_masters')
            ->orderBy('id')
            ->chunk(100, function ($taskMasters) use ($progressBar): void {
                foreach ($taskMasters as $taskMaster) {
                    $progressBar->advance();
                    try {
                        DB::transaction(function () use ($taskMaster): void {
                            $tasks = DB::connection('mysql2')
                                ->table('tasks')
                                ->where('task_master_id', $taskMaster->id)
                                ->get(['employee_id', 'service_id', 'customer_id', 'start_time', 'end_time', 'created_by', 'updated_by']);

                            $customer_id = $tasks->max('customer_id');
                            $account_id = Account::where('second_reference_no', $customer_id)->value('id');
                            $created_by = User::where('type', 'user')->where('second_reference_no', $taskMaster->created_by)->value('id');
                            $updated_by = User::where('type', 'user')->where('second_reference_no', $taskMaster->updated_by)->value('id');

                            if (! $account_id) {
                                throw new \Exception('Account not found for ID: '.$customer_id);
                            }
                            $start_time = $taskMaster->date.' '.$tasks->min('start_time');
                            $end_time = $taskMaster->date.' '.$tasks->max('end_time');
                            if (strtotime($end_time) < strtotime($start_time)) {
                                $end_time = date('Y-m-d H:i:s', strtotime($start_time) + 60);
                            }
                            $status = $taskMaster->status == 1 ? 'completed' : 'pending';

                            $data = [
                                'branch_id' => 1,
                                'account_id' => $account_id,
                                'start_time' => $start_time,
                                'end_time' => $end_time,
                                'color' => $taskMaster->color,
                                'status' => $status,
                                'created_by' => $created_by,
                                'updated_by' => $updated_by,
                            ];
                            $items = [];
                            foreach ($tasks as $task) {
                                $employee_id = User::where('type', 'employee')->where('second_reference_no', $task->employee_id)->value('id');
                                $service_id = Product::where('type', 'service')->where('second_reference_no', $task->service_id)->value('id');
                                $created_by = User::where('type', 'user')->where('second_reference_no', $task->created_by)->value('id');
                                $updated_by = User::where('type', 'user')->where('second_reference_no', $task->updated_by)->value('id');
                                if (! $employee_id) {
                                    throw new \Exception('Employee not found for ID: '.$task->employee_id);
                                }
                                if (! $service_id) {
                                    throw new \Exception('Service not found for ID: '.$task->service_id);
                                }
                                $item = [
                                    'service_id' => $service_id,
                                    'employee_id' => $employee_id,
                                    'created_by' => $created_by,
                                    'updated_by' => $updated_by,
                                ];

                                // Check for duplicates before adding
                                $exists = collect($items)->contains(function ($existingItem) use ($item) {
                                    return $existingItem['service_id'] == $item['service_id'] && $existingItem['employee_id'] == $item['employee_id'];
                                });

                                if (! $exists) {
                                    $items[] = $item;
                                }
                            }
                            $data['items'] = $items;
                            $response = (new AppointmentCreateAction())->execute($data, 1);
                            if (! $response['success']) {
                                $this->error('Failed to create appointment: '.$response['message']);
                                Log::error('Failed to create appointment: '.$response['message']);
                                Log::error($data);
                            }
                        });
                    } catch (\Exception $e) {
                        $this->error('Error migrating appointment: '.$e->getMessage());
                        Log::error('Appointment migration error: '.$e->getMessage());
                    }
                }
            });
        $progressBar->finish();
        $this->info('Appointments migration completed.');
    }

    private function accounts()
    {
        $account_heads = DB::connection('mysql2')
            ->table('account_heads')
            ->whereIn('id', $this->paymentModesIds)
            ->get();
        foreach ($account_heads as $value) {
            $name = ucfirst(strtolower($value->name));
            $data = [
                'second_reference_no' => $value->id,
                'account_type' => 'asset',
                'name' => $name,
            ];
            Account::updateOrCreate([
                    'account_type' => $data['account_type'],
                    'name' => $data['name'],
                ], $data
            );
        }
    }

    private function purchases()
    {
        $this->info('Migrating purchases...');

        // Get total count for progress bar
        $totalPurchases = DB::connection('mysql2')
            ->table('purchases')
            ->whereNull('deleted_at')
            ->count();

        $progressBar = $this->output->createProgressBar($totalPurchases);
        $progressBar->start();

        DB::connection('mysql2')
            ->table('purchases')
            ->whereNull('purchases.deleted_at')
            ->orderBy('purchases.id')
            ->chunk(100, function ($purchases) use ($progressBar): void {
                foreach ($purchases as $purchase) {
                    $progressBar->advance();
                    try {
                        DB::transaction(function () use ($purchase): void {
                            $account = Account::where('second_reference_no', $purchase->vendor_id)->first();
                            $created_by = User::where('type', 'user')->where('second_reference_no', $purchase->created_by)->value('id');
                            $updated_by = User::where('type', 'user')->where('second_reference_no', $purchase->updated_by)->value('id');
                            $data = [
                                'branch_id' => $purchase->branch_id,
                                'date' => $purchase->date,
                                'delivery_date' => $purchase->delivery_date,
                                'invoice_no' => $purchase->invoice_no,
                                'account_id' => $account->id,
                                'other_discount' => $purchase->other_discount,
                                'freight' => 0,
                                'status' => 'completed',
                                'created_by' => $created_by,
                                'updated_by' => $updated_by,
                            ];
                            $data['items'] = [];
                            $purchase_items = DB::connection('mysql2')
                                ->table('purchase_items')
                                ->whereNull('deleted_at')
                                ->where('purchase_id', $purchase->id)
                                ->get();
                            foreach ($purchase_items as $value) {
                                $product_id = Product::where('type', 'product')->where('second_reference_no', $value->product_id)->value('id');
                                $inventory_id = Inventory::where('product_id', $product_id)->value('id');
                                if (! $inventory_id) {
                                    throw new \Exception('Inventory not found for product ID: '.$value->product_id);
                                }
                                $item = [
                                    'inventory_id' => $inventory_id,
                                    'product_id' => $product_id,
                                    'batch' => $value->batch,
                                    'unit_price' => $value->unit_price,
                                    'net_amount' => $value->unit_price * $value->quantity,
                                    'quantity' => $value->quantity,
                                    'discount' => $value->discount,
                                    'tax' => 0,
                                    'total' => ($value->unit_price * $value->quantity) - $value->discount,
                                ];
                                $data['items'][] = $item;
                            }
                            $data['items'] = collect($data['items']);
                            $data['gross_amount'] = $data['items']->sum('net_amount');
                            $data['item_discount'] = $data['items']->sum('discount');
                            $data['total_quantity'] = $data['items']->sum('quantity');
                            $data['total'] = $data['items']->sum('total');
                            $journals = DB::connection('mysql2')
                                ->table('journals')
                                ->whereNull('deleted_at')
                                ->where('purchase_id', $purchase->id)
                                ->whereIn('credit', $this->paymentModesIds)
                                ->get();
                            $data['payments'] = [];
                            foreach ($journals as $value) {
                                $account_id = Account::where('second_reference_no', $value->credit)->value('id');
                                $journal = [
                                    'payment_method_id' => $account_id,
                                    'amount' => $value->amount,
                                ];
                                $data['payments'][] = $journal;
                            }
                            $data['payments'] = collect($data['payments']);
                            $data['paid'] = $data['payments']->sum('amount');
                            $response = (new PurchaseCreateAction())->execute($data, 1);
                            if (! $response['success']) {
                                $this->error('Failed to create purchase: '.$response['message']);
                                Log::error('Failed to create purchase: '.$response['message']);
                                Log::error($data);
                            }
                        });
                    } catch (\Exception $e) {
                        Log::error('Purchase migration error: '.$e->getMessage());
                    }
                }
            });
        $progressBar->finish();
        $this->newLine();
        $this->info('Purchase migration completed successfully!');
    }

    private function sales()
    {
        $this->info('Migrating sales...');

        // The sales replay is the heaviest phase (~100k+ rows). Instead of processing it inline, split
        // it into 500-sale chunks dispatched as a Bus batch onto the dedicated `migration` queue so
        // MANY workers drain it in parallel. MigrateSalesChunkJob rebuilds the in-memory lookup maps
        // once per worker process (statically memoised), so we don't lose the "preload once" win.
        //
        // Fast-path: the jobs run in BulkImport mode (no inventory lock, no per-line product-cost
        // recompute, no out-of-stock prevention) so parallel workers never serialise on hot products.
        // That leaves Inventory.quantity racy during the run, so once the batch finishes we reconcile
        // every inventory's quantity from the (race-proof) InventoryLog deltas and recompute product
        // costs once — see reconcileInventoryAndCosts(). Accounting is insert-only and always correct.
        $saleIds = DB::connection('mysql2')
            ->table('sales')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->pluck('id');

        $totalSales = $saleIds->count();
        if ($totalSales === 0) {
            $this->info('No sales to migrate.');

            return;
        }

        $jobs = $saleIds
            ->chunk(500)
            ->map(fn ($chunk) => new MigrateSalesChunkJob($chunk->values()->all(), array_values($this->paymentModesIds), $this->tenantId))
            ->all();

        $this->info("Queuing {$totalSales} sales as ".count($jobs).' batched jobs (500 each) on the `migration` queue.');
        $this->warn('Start one or more workers in other terminals to drain them (run several for more speed):');
        $this->warn('  php artisan queue:work --queue=migration --stop-when-empty --tries=1 --timeout=3600');

        // Dispatch explicitly onto the database connection so the command-wide queue.default=sync
        // (set in handle() for the inline inventory jobs) does not force these to run in-process.
        $batch = Bus::batch($jobs)
            ->name('sales-migration')
            ->onConnection('database')
            ->onQueue('migration')
            ->allowFailures()
            ->dispatch();

        // salesReturns() runs immediately after this method and looks the migrated sales up by
        // invoice_no, so block here until the worker has drained the batch. Progress is tracked over
        // jobs (the batch's unit of work), not individual sales.
        $progressBar = $this->output->createProgressBar(count($jobs));
        $progressBar->start();
        $lastProcessed = 0;
        while (true) {
            $batch = $batch->fresh();
            if (! $batch) {
                break;
            }
            $processed = $batch->totalJobs - $batch->pendingJobs;
            if ($processed > $lastProcessed) {
                $progressBar->advance($processed - $lastProcessed);
                $lastProcessed = $processed;
            }
            if ($batch->finished()) {
                break;
            }
            sleep(2);
        }
        $progressBar->finish();
        $this->newLine();

        if ($batch && $batch->failedJobs > 0) {
            $this->warn("Sales migration finished with {$batch->failedJobs} failed chunk job(s) — check the log and job_batches/failed_jobs.");
        }

        // The parallel workers left Inventory.quantity racy (BulkImport mode). Now that every sale is
        // committed, rebuild the exact quantities from the InventoryLog movement deltas and recompute
        // product costs once. Must run before salesReturns(), which reads/adjusts these quantities.
        $this->reconcileInventoryAndCosts();

        $this->info('Sales migration completed successfully!');
    }

    /**
     * Post-parallel-replay reconciliation. During the batch the workers ran in BulkImport mode, so
     * Inventory.quantity was written without locks (racy) and product costs were not recomputed. Both
     * are rebuilt here from source-of-truth data that is immune to those races:
     *  - inventory.quantity  = SUM(quantity_in) - SUM(quantity_out) of that product/branch's
     *    InventoryLog rows. Each log delta equals the movement's own amount regardless of the stale
     *    read it was computed against, and every movement (opening create, purchase, sale, ...) is
     *    logged, so the running total is exact.
     *  - products.cost       = weighted-average cost across the product's inventories (what
     *    LogInventoryAction maintains per-write in normal operation), computed once at the end.
     */
    private function reconcileInventoryAndCosts(): void
    {
        $this->info('Reconciling inventory quantities from movement logs...');

        // inventory.quantity <- net of InventoryLog deltas, per product+branch (one inventory row per
        // product+branch in this dataset). Done as a correlated UPDATE so it is a single statement.
        DB::statement('
            UPDATE inventories i
            JOIN (
                SELECT product_id, branch_id,
                       COALESCE(SUM(quantity_in), 0) - COALESCE(SUM(quantity_out), 0) AS net
                FROM inventory_logs
                GROUP BY product_id, branch_id
            ) l ON l.product_id = i.product_id AND l.branch_id = i.branch_id
            SET i.quantity = l.net
        ');

        $this->info('Recomputing product costs (weighted average)...');

        // products.cost <- SUM(cost*quantity) / SUM(quantity) across each product's inventories.
        DB::statement('
            UPDATE products p
            JOIN (
                SELECT product_id,
                       SUM(cost * quantity) AS total_cost,
                       SUM(quantity)        AS total_qty
                FROM inventories
                GROUP BY product_id
                HAVING total_qty > 0
            ) c ON c.product_id = p.id
            SET p.cost = ROUND(c.total_cost / c.total_qty, 2)
        ');

        $this->info('Inventory and cost reconciliation completed.');
    }

    private function salesReturns()
    {
        $this->info('Migrating sales returns...');

        // Get total count for progress bar
        $totalSaleReturns = DB::connection('mysql2')
            ->table('sale_returns')
            ->whereNull('deleted_at')
            ->count();

        $progressBar = $this->output->createProgressBar($totalSaleReturns);
        $progressBar->start();

        DB::connection('mysql2')
            ->table('sale_returns')
            ->whereNull('sale_returns.deleted_at')
            ->orderBy('sale_returns.id')
            ->chunk(100, function ($saleReturns) use ($progressBar): void {
                foreach ($saleReturns as $saleReturn) {
                    $progressBar->advance();
                    try {
                        DB::transaction(function () use ($saleReturn): void {
                            $account = Account::where('second_reference_no', $saleReturn->customer_id)->first();
                            $created_by = User::where('type', 'user')->where('second_reference_no', $saleReturn->created_by)->value('id');
                            $updated_by = User::where('type', 'user')->where('second_reference_no', $saleReturn->updated_by)->value('id');
                            $branch_id = Branch::where('id', $saleReturn->branch_id)->value('id') ?? 1;

                            $data = [
                                'branch_id' => $branch_id,
                                'date' => $saleReturn->date,
                                'reference_no' => $saleReturn->invoice_no,
                                'account_id' => $account->id,
                                'tax_amount' => 0,
                                'other_discount' => $saleReturn->other_discount ?: 0,
                                'freight' => 0,
                                'status' => 'completed',
                                'created_by' => $created_by,
                                'updated_by' => $updated_by,
                            ];

                            $data['items'] = [];

                            // Migrate sale return product items
                            $sale_return_items = DB::connection('mysql2')
                                ->table('sale_return_items')
                                ->whereNull('deleted_at')
                                ->where('sale_return_id', $saleReturn->id)
                                ->get();

                            foreach ($sale_return_items as $value) {

                                $sale_id = null;
                                $sale_item_id = null;

                                $secondSale = DB::connection('mysql2')->table('sales')->find($value->sale_id);
                                if ($secondSale) {
                                    $sale = Sale::where('invoice_no', $secondSale->invoice_no)->first();
                                    if ($sale) {
                                        $sale_id = $sale->id;
                                        $secondSaleItem = SaleItem::where('sale_id', $sale_id)->where('product_id', $value->product_id)->first();
                                        if ($secondSaleItem) {
                                            $sale_item_id = $secondSaleItem->id;
                                        }
                                    }
                                }

                                $product_id = Product::where('type', 'product')->where('second_reference_no', $value->product_id)->value('id');
                                $inventory_id = Inventory::where('branch_id', $branch_id)->where('product_id', $product_id)->value('id');

                                if (! $inventory_id) {
                                    throw new \Exception('Inventory not found for product ID: '.$value->product_id.' for the branch ID: '.$branch_id);
                                }

                                $item = [
                                    'sale_id' => $sale_id,
                                    'sale_item_id' => $sale_item_id,
                                    'inventory_id' => $inventory_id,
                                    'product_id' => $product_id,
                                    'unit_price' => $value->unit_price,
                                    'quantity' => $value->quantity,
                                    'gross_total' => $value->unit_price * $value->quantity,
                                    'discount' => $value->discount,
                                    'tax' => 0,
                                    'total' => $value->unit_price * $value->quantity,
                                ];
                                $data['items'][] = $item;
                            }

                            $data['items'] = collect($data['items']);

                            $data['gross_amount'] = $data['items']->sum('net_amount');
                            $data['item_discount'] = $data['items']->sum('discount');
                            $data['total_quantity'] = $data['items']->sum('quantity');
                            $data['total'] = $data['items']->sum('total');

                            // Migrate sale return payments
                            $journals = DB::connection('mysql2')
                                ->table('journals')
                                ->whereNull('deleted_at')
                                ->where('sale_return_id', $saleReturn->id)
                                ->whereIn('credit', $this->paymentModesIds)
                                ->get(['amount', 'credit']);

                            $data['payments'] = [];
                            foreach ($journals as $value) {
                                $account_id = Account::where('second_reference_no', $value->credit)->value('id');
                                $journal = [
                                    'payment_method_id' => $account_id,
                                    'amount' => $value->amount,
                                ];
                                $data['payments'][] = $journal;
                            }
                            $response = (new SaleReturnCreateAction())->execute($data, 1);
                            if (! $response['success']) {
                                $this->error('Failed to create sale return: '.$response['message']);
                                Log::error('Failed to create sale return: '.$response['message']);
                                Log::error($data);
                            }
                        });
                    } catch (\Exception $e) {
                        $this->error('Error migrating sale return: '.$e->getMessage());
                        Log::error('Sale return migration error: '.$e->getMessage());
                    }
                }
            });

        $progressBar->finish();
        $this->newLine();
        $this->info('Sales returns migration completed successfully!');
    }

    private function users()
    {
        try {
            $this->info('Migrating users...');
            DB::connection('mysql2')
                ->table('users')
                ->join('user_types', 'user_role_id', '=', 'user_types.id')
                ->whereNull('users.deleted_at')
                ->select(['users.*', 'user_types.name as user_type_name'])
                ->orderBy('users.id')
                ->chunk(100, function ($users): void {
                    foreach ($users as $item) {
                        try {
                            DB::transaction(function () use ($item): void {
                                $name = ucfirst(strtolower($item->name));
                                $user = User::create([
                                    'type' => 'user',
                                    'code' => rand(100000, 999999),
                                    'second_reference_no' => $item->id,
                                    'name' => $name,
                                    'email' => strtolower($item->email ?? $name.'@astra.com'),
                                    'mobile' => $item->mobile,
                                    'password' => $item->password ?: Hash::make('asdasd'),
                                    'created_at' => $item->created_at,
                                    'updated_at' => $item->updated_at,
                                ]);
                                Role::firstOrCreate(['name' => ucfirst(strtolower($item->user_type_name))]);

                                $user->assignRole($item->user_type_name);

                                $single = [
                                    'user_id' => $user->id,
                                    'branch_id' => 1,
                                ];
                                UserHasBranch::create($single);
                                $user->update(['default_branch_id' => 1]);
                            });
                            $this->info("Created user: {$item->name}");
                        } catch (\Exception $e) {
                            $this->error("Failed to create user {$item->name}: {$e->getMessage()}");
                            Log::error("Employee creation error for {$item->name}: {$e->getMessage()}");

                            continue;
                        }
                    }
                    $this->info('Processed '.count($users).' users');
                });

            $this->info('Employee migration completed successfully');
        } catch (\Exception $e) {
            $this->error('Error migrating employees: '.$e->getMessage());
            Log::error('Employee migration error: '.$e->getMessage());
        }
    }

    private function employees()
    {
        try {
            $this->info('Migrating employees...');
            DB::connection('mysql2')
                ->table('employees')
                ->join('designations', 'designation_id', '=', 'designations.id')
                // ->whereNull('employees.deleted_at')
                ->select(['employees.*', 'designations.name as designation_name'])
                ->orderBy('employees.id')
                ->chunk(100, function ($employees): void {
                    foreach ($employees as $item) {
                        try {
                            DB::transaction(function () use ($item): void {
                                // Create user
                                $name = ucfirst(strtolower($item->name));
                                $user = User::create([
                                    'type' => 'employee',
                                    'code' => rand(100000, 999999),
                                    'second_reference_no' => $item->id,
                                    'name' => $name,
                                    'email' => strtolower($item->email ?? $name.'@astra.com'),
                                    'mobile' => $item->mobile,
                                    'place' => $item->place,
                                    'allowance' => $item->allowance,
                                    'salary' => $item->salary,
                                    'hra' => $item->hra,
                                    'dob' => $item->dob,
                                    'doj' => $item->doj,
                                    'pin' => $item->pin,
                                    'password' => $item->password ?: Hash::make('asdasd'),
                                    'created_at' => $item->created_at,
                                    'updated_at' => $item->updated_at,
                                ]);
                                Role::firstOrCreate(['name' => ucfirst(strtolower($item->designation_name))]);

                                $user->assignRole($item->designation_name);

                                $single = [
                                    'user_id' => $user->id,
                                    'branch_id' => 1,
                                ];
                                UserHasBranch::create($single);
                                $user->update(['default_branch_id' => 1]);
                            });
                            $this->info("Created user: {$item->name}");
                        } catch (\Exception $e) {
                            $this->error("Failed to create user {$item->name}: {$e->getMessage()}");
                            Log::error("Employee creation error for {$item->name}: {$e->getMessage()}");

                            continue;
                        }
                    }
                    $this->info('Processed '.count($employees).' employees');
                });

            $this->info('Employee migration completed successfully');
        } catch (\Exception $e) {
            $this->error('Error migrating employees: '.$e->getMessage());
            Log::error('Employee migration error: '.$e->getMessage());
        }
    }

    private function employeeCommissions()
    {
        $this->info('Migrating employee commissions...');

        // Old accounts DB splits commissions across two tables:
        //   product_commissions      (product_id     -> old products.id)
        //   spa_service_commissions  (spa_service_id -> old spa_services.id)
        // New project_manager DB unifies them into employee_commissions, where product_id
        // points at the unified products table (services are products with type='service')
        // and employee_id points at users (type='employee'). The new table has a single
        // commission_percentage column, so the old `ot_commission` has no target and is
        // NOT migrated.
        $sources = [
            // [old table, old item-id column, new product type]
            ['product_commissions', 'product_id', 'product'],
            ['spa_service_commissions', 'spa_service_id', 'service'],
        ];

        $total = 0;
        foreach ($sources as [$table]) {
            $total += DB::connection('mysql2')->table($table)->count();
        }

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($sources as [$table, $itemColumn, $productType]) {
            DB::connection('mysql2')
                ->table($table)
                ->orderBy('id')
                ->chunk(200, function ($rows) use ($progressBar, $itemColumn, $productType, $table): void {
                    foreach ($rows as $row) {
                        $progressBar->advance();
                        try {
                            $itemRef = $row->{$itemColumn};

                            $product_id = Product::where('type', $productType)
                                ->where('second_reference_no', $itemRef)
                                ->value('id');
                            $employee_id = User::where('type', 'employee')
                                ->where('second_reference_no', $row->employee_id)
                                ->value('id');

                            if (! $product_id || ! $employee_id) {
                                Log::warning("Employee commission skipped from {$table}: {$productType} ref {$itemRef} or employee ref {$row->employee_id} not found.");

                                continue;
                            }

                            // updateOrInsert on the unique key (tenant_id, product_id, employee_id)
                            // keeps this idempotent. Old tables carry no timestamps, so stamp now().
                            DB::table('employee_commissions')->updateOrInsert(
                                [
                                    'tenant_id' => 1,
                                    'product_id' => $product_id,
                                    'employee_id' => $employee_id,
                                ],
                                [
                                    'commission_percentage' => $row->commission ?? 0,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]
                            );
                        } catch (\Exception $e) {
                            $this->error('Error migrating employee commission: '.$e->getMessage());
                            Log::error('Employee commission migration error: '.$e->getMessage());
                        }
                    }
                });
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Employee commissions migration completed.');
    }

    private function products()
    {
        $this->info('Migrating products...');
        $productsCount = DB::connection('mysql2')->table('products')
            ->whereNull('deleted_at')
            ->count();
        $this->info("Total products to migrate: {$productsCount}");
        $products = DB::connection('mysql2')
            ->table('products')
            ->whereNull('products.deleted_at')
            ->leftJoin('brands', 'brand_id', '=', 'brands.id')
            ->leftJoin('categories', 'category_id', '=', 'categories.id')
            ->leftJoin('units', 'unit_id', '=', 'units.id')
            ->get([
                'products.*',
                'brands.name as brand_name',
                'categories.name as category_name',
                'units.name as unit_name',
            ]);
        $progressBar = $this->output->createProgressBar($productsCount);
        $progressBar->start();
        foreach ($products as $item) {
            $progressBar->advance();
            $name = ucfirst(strtolower($item->name));
            if ($name == 'Shampoo') {
                $name .= '.';
            }
            if ($name == 'Eyelashes') {
                $name .= '.';
            }
            $serviceData = [
                'type' => 'product',
                'code' => $item->code ?: rand(100000, 999999),
                'second_reference_no' => $item->id,
                'name' => $name,
                'name_arabic' => $item->name_arabic,
                'department' => 'Service',
                'main_category' => ucfirst(strtolower($item->category_name ?: 'General')),
                'sub_category' => '',
                'cost' => $item->cost,
                'unit' => ucfirst(strtolower($item->unit_name)),
                'brand_id' => ucfirst(strtolower($item->brand_name)),
                'mrp' => $item->mrp ?: 0,
                'tax' => $item->tax,
                'priority' => $item->priority ? $item->priority : 0,
                'size' => $item->size,
                'barcode_number' => $item->barcode,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
            $data = Product::constructData($serviceData, 1);
            unset($data['department']);
            unset($data['unit']);
            unset($data['main_category']);
            unset($data['sub_category']);
            $product = Product::create((array) $data);
            $branches = Branch::all();
            foreach ($branches as $branch) {
                BranchProductCreationJob::dispatch($branch->id, 1, $product->id);
            }
        }
        $progressBar->finish();
    }

    private function service()
    {
        $this->info('Migrating services...');
        $services = DB::connection('mysql2')
            ->table('spa_services')
            ->join('spa_service_groups', 'spa_service_group_id', '=', 'spa_service_groups.id')
            ->get(['spa_services.*', 'spa_service_groups.name as group_name']);
        foreach ($services as $item) {
            $serviceData = [
                'type' => 'service',
                'code' => rand(100000, 999999),
                'second_reference_no' => $item->id,
                'name' => $item->name,
                'name_arabic' => $item->arabic_name,
                'description' => $item->description,
                'department' => 'Service',
                'main_category' => ucfirst(strtolower($item->group_name)),
                'sub_category' => '',
                'cost' => $item->price,
                'mrp' => $item->price,
                'time' => $item->time,
                'priority' => $item->priority ? $item->priority : 0,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
            $data = Product::constructData($serviceData, 1);
            unset($data['department']);
            unset($data['unit']);
            unset($data['main_category']);
            unset($data['sub_category']);
            $exists = Product::service()->where('name', $data['name'])->exists();
            if (! $exists) {
                $product = Product::create((array) $data);
                BranchProductCreationJob::dispatch(1, 1, $product->id);
            }
        }
    }

    private function vendor()
    {
        $this->info('Migrating vendors...');
        $vendors = DB::connection('mysql2')->table('vendors')
            ->join('account_heads', 'vendors.account_head_id', '=', 'account_heads.id')
            ->get();
        foreach ($vendors as $vendor) {
            $vendorData = [
                'account_type' => 'liability',
                'tenant_id' => $this->tenantId,
                'second_reference_no' => $vendor->account_head_id,
                'model' => 'vendor',
                'name' => ucfirst(strtolower($vendor->name)),
                'email' => $vendor->email,
                'mobile' => $vendor->mobile,
                'place' => $vendor->place,
                'created_at' => $vendor->created_at,
                'updated_at' => $vendor->updated_at,
            ];
            DB::table('accounts')->insertOrIgnore((array) $vendorData);
        }
    }

    private function customer()
    {
        $this->info('Migrating customers...');
        $customers = DB::connection('mysql2')->table('customers')
            ->join('account_heads', 'customers.account_head_id', '=', 'account_heads.id')
            ->where('account_heads.id', '!=', 2)
            ->get();
        foreach ($customers as $customer) {
            $name = explode('@', $customer->name);
            $nationality = $customer->nationality;
            switch ($nationality) {
                case 'INDIAN/tamel':
                case 'INDIAN/KERALA':
                case 'KERALA':
                case 'keral':
                case 'indian':
                case 'INDIAN':
                    $nationality = 'India';
                    break;
                case 'QATARI':
                case 'QATARY':
                case 'QATAR':
                    $nationality = 'Qatar';
                    break;
                case 'EGYPTIAN':
                case 'EGYP':
                case 'egyp':
                    $nationality = 'Egypt';
                    break;
                case 'NIGERIA':
                    $nationality = 'Nigeria';
                    break;
                case 'moroccan':
                    $nationality = 'Morocco';
                    break;
                case 'PHILIPINES':
                    $nationality = 'Philippines';
                    break;
                case 'SAUDI':
                    $nationality = 'Saudi Arabia';
                    break;
                case 'tunisian':
                    $nationality = 'Tunisia';
                    break;
                case 'SERIA':
                    $nationality = 'Syria';
                    break;
                case 'Pakistanis':
                    $nationality = 'Pakistan';
                    break;
            }
            $customerData = [
                'account_type' => 'asset',
                'tenant_id' => $this->tenantId,
                'second_reference_no' => $customer->account_head_id,
                'model' => 'customer',
                'name' => ucfirst(strtolower($name[0])),
                'email' => $customer->email,
                'mobile' => $customer->mobile,
                'whatsapp_mobile' => $customer->whatsapp_no,
                'nationality' => $nationality,
                'dob' => $customer->dob !== '0000-00-00' ? $customer->dob : null,
                'id_no' => $customer->id_no,
                'company' => $customer->company,
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
            ];
            DB::table('accounts')->insertOrIgnore((array) $customerData);
        }
    }
}
