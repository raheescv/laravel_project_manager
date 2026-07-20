<?php

namespace App\Jobs;

use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use App\Support\Migration\BulkImport;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Migrates one 500-sale chunk of the mysql2 -> mysql sales replay (see MigrateDataCommand::sales()).
 *
 * Dispatched as part of a Bus batch on the `migration` queue so the huge sales replay can be drained
 * by MANY workers in parallel instead of blocking the migration command inline.
 *
 * Fast-path parallelism: the sale pipeline has two things that would serialise workers on a hot
 * product — the non-atomic Inventory.quantity read-modify-write in StockUpdateAction, and the
 * per-line weighted-average product-cost recompute in LogInventoryAction that writes products.cost.
 * Rather than lock around them (which caps throughput on popular items), this job enables
 * BulkImport mode for the worker process: the cost recompute and out-of-stock prevention are
 * skipped, and the racy Inventory.quantity writes are left alone. The InventoryLog delta rows are
 * still written (their in/out amounts are correct regardless of the race), and MigrateDataCommand
 * reconciles every inventory.quantity from those deltas — and recomputes product costs — in one
 * pass AFTER the batch finishes. So stock/cost are exact at the END, not on every intermediate write.
 */
class MigrateSalesChunkJob implements ShouldQueue
{
    use Batchable, Queueable;

    /** No automatic retries: each sale is already wrapped in its own try/catch + transaction below. */
    public $tries = 1;

    public $timeout = 3600;

    /**
     * Per-worker-process memo of the small target-DB lookup tables, keyed by tenant id. The same
     * worker processes every chunk sequentially, so building these id->id maps once here restores the
     * "preload once" benefit the inline command had, without serialising them into each job payload.
     * The maps hold only stable id lookups (never stock/quantity), so they stay valid as sales deduct
     * inventory.
     */
    protected static array $maps = [];

    /**
     * @param  array<int>  $saleIds  Source (mysql2) sales.id values for this chunk.
     * @param  array<int>  $paymentModesIds  Old account_head ids that are payment modes.
     */
    public function __construct(
        public array $saleIds,
        public array $paymentModesIds,
        public int $tenantId,
        public int $userId = 1,
    ) {}

    public function handle(): void
    {
        // Turn on bulk-import mode for THIS worker process: skips the per-line product-cost recompute
        // (LogInventoryAction) and out-of-stock prevention (OutOfStockSales) so parallel workers don't
        // serialise on hot products. Stock/cost are reconciled by MigrateDataCommand after the batch.
        BulkImport::enable();

        // Queue workers have no request, so the tenant global scope would otherwise resolve to null
        // and every scoped read/write would target the wrong rows. Pin it explicitly for this job.
        $tenant = Tenant::find($this->tenantId);
        if ($tenant) {
            app(TenantService::class)->setCurrentTenant($tenant);
        }

        // The command disables FK checks for the whole migration on its own DB session; the worker
        // runs in a separate session, so disable them here too to match the inline replay's behaviour.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // The journal-entry actions read branch/account ids from long-lived caches primed by the
        // command. File cache is shared across processes so they normally already exist, but prime
        // them defensively in case this worker started before the command primed them.
        $this->ensureLookupCaches();

        [$accountMap, $userMap, $employeeMap, $productMap, $serviceMap, $inventoryMap] = $this->maps();

        // Pull this chunk's parent sales rows and prefetch their child rows from the source DB in one
        // query each (indexed on sale_id) instead of three queries per sale, then group in PHP.
        $sales = DB::connection('mysql2')
            ->table('sales')
            ->whereNull('deleted_at')
            ->whereIn('id', $this->saleIds)
            ->orderBy('id')
            ->get();

        // Idempotency: skip any invoice_no already present in the target sales table so a re-run
        // (or a redelivered job) does not create duplicate sales. One query for the whole chunk;
        // flipped to a key set for O(1) lookups. Sale is tenant-scoped (pinned above).
        $existingInvoiceNos = Sale::whereIn('invoice_no', $sales->pluck('invoice_no')->filter()->all())
            ->pluck('invoice_no')
            ->flip();

        $serviceItemsBySale = DB::connection('mysql2')
            ->table('sale_service_items')
            ->whereNull('deleted_at')
            ->whereIn('sale_id', $this->saleIds)
            ->get()
            ->groupBy('sale_id');

        $itemsBySale = DB::connection('mysql2')
            ->table('sale_items')
            ->select(
                'sale_id',
                'product_id',
                'employee_id',
                'unit_price',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(discount) as total_discount')
            )
            ->whereNull('deleted_at')
            ->whereIn('sale_id', $this->saleIds)
            ->groupBy('sale_id', 'product_id', 'employee_id', 'unit_price')
            ->get()
            ->groupBy('sale_id');

        $journalsBySale = DB::connection('mysql2')
            ->table('journals')
            ->whereNull('deleted_at')
            ->whereIn('sale_id', $this->saleIds)
            ->whereIn('debit', $this->paymentModesIds)
            ->get(['sale_id', 'amount', 'debit'])
            ->groupBy('sale_id');

        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($sales as $sale) {
            try {
                // Already migrated under this invoice_no — skip to keep the replay idempotent.
                if ($sale->invoice_no !== null && $existingInvoiceNos->has($sale->invoice_no)) {
                    $skipped++;

                    continue;
                }

                // Build the sale payload (pure map lookups, no writes) then create it. No inventory
                // lock: BulkImport mode lets workers race the Inventory.quantity write, and the
                // command reconciles the exact quantity from InventoryLog deltas after the batch.
                // Items are ordered by inventory_id (see buildSaleData) so all sales touch inventory
                // rows in the same order, which prevents most worker-vs-worker deadlocks; the retry
                // below mops up the residual ones.
                $data = $this->buildSaleData($sale, $accountMap, $userMap, $employeeMap, $productMap, $serviceMap, $inventoryMap, $serviceItemsBySale, $itemsBySale, $journalsBySale);

                $this->createSaleWithRetry($data, $sale->id, $created, $failed);
            } catch (Throwable $e) {
                $failed++;
                Log::error('Sales migration error (sale_id '.$sale->id.'): '.$e->getMessage());
            }
        }

        Log::info(sprintf(
            'MigrateSalesChunkJob complete: %d sales in chunk — %d created, %d skipped (already migrated), %d failed.%s',
            $sales->count(),
            $created,
            $skipped,
            $failed,
            $this->batch() ? ' [batch '.$this->batch()->id.']' : ''
        ));
    }

    /**
     * Create one sale inside a transaction, retrying on InnoDB deadlock / lock-wait timeout with a
     * little jittered backoff. SaleCreateAction swallows its own query exceptions, so we detect a
     * concurrency failure from the returned message and re-run the whole sale rather than relying on
     * DB::transaction's built-in retry. Increments $created / $failed (passed by reference).
     */
    protected function createSaleWithRetry(array $data, $sourceSaleId, int &$created, int &$failed): void
    {
        $maxAttempts = 5;

        for ($attempt = 1; ; $attempt++) {
            try {
                $ok = DB::transaction(function () use ($data): bool {
                    $response = (new SaleCreateAction())->execute($data, $this->userId);
                    if (! $response['success']) {
                        // Bubble concurrency failures up to the retry loop; log the rest as final.
                        if ($this->isConcurrencyError($response['message'])) {
                            throw new RuntimeException($response['message']);
                        }
                        Log::error('Failed to create sale: '.$response['message']);
                        Log::error($data);

                        return false;
                    }

                    return true;
                });

                $ok ? $created++ : $failed++;

                return;
            } catch (Throwable $e) {
                if ($attempt < $maxAttempts && $this->isConcurrencyError($e->getMessage())) {
                    // 20-140ms jittered backoff, growing per attempt, to break the deadlock cycle.
                    usleep(random_int(20_000, 60_000) + $attempt * 20_000);

                    continue;
                }

                $failed++;
                Log::error('Sales migration error (sale_id '.$sourceSaleId.') after '.$attempt.' attempt(s): '.$e->getMessage());

                return;
            }
        }
    }

    /**
     * Whether an error message looks like a transient InnoDB concurrency failure worth retrying.
     */
    protected function isConcurrencyError(string $message): bool
    {
        return str_contains($message, 'Deadlock found when trying to get lock')
            || str_contains($message, 'Lock wait timeout exceeded')
            || str_contains($message, '1213')
            || str_contains($message, '40001');
    }

    /**
     * Build the SaleCreateAction payload for one source sale from the prefetched child rows and the
     * in-memory lookup maps. Pure (no writes); throws only for unmappable account/service inventory.
     */
    protected function buildSaleData($sale, $accountMap, $userMap, $employeeMap, $productMap, $serviceMap, $inventoryMap, $serviceItemsBySale, $itemsBySale, $journalsBySale): array
    {
        $account_id = $accountMap[$sale->customer_id] ?? null;
        if (! $account_id) {
            throw new Exception('Account not found for customer ID: '.$sale->customer_id);
        }
        $data = [
            'branch_id' => $sale->branch_id,
            'date' => $sale->date,
            'due_date' => $sale->due_date,
            'invoice_no' => $sale->invoice_no,
            'sale_type' => 'normal',
            'account_id' => $account_id,
            'customer_name' => $sale->customer_name,
            'customer_mobile' => $sale->customer_mobile,
            'tax_amount' => 0,
            'other_discount' => $sale->other_discount ? $sale->other_discount : 0,
            'freight' => 0,
            'grand_total' => $sale->grand_total,
            'paid' => $sale->paid ? $sale->paid : 0,
            'balance' => $sale->balance,
            'address' => null,
            'status' => $sale->status == 2 ? 'completed' : 'draft',
            'created_by' => $userMap[$sale->created_by] ?? null,
            'updated_by' => $userMap[$sale->updated_by] ?? null,
        ];
        $data['comboOffers'] = [];
        $data['items'] = [];
        foreach ($serviceItemsBySale[$sale->id] ?? [] as $value) {
            $product = $serviceMap[$value->spa_service_id] ?? null;
            $product_id = $product?->id;
            $inventory_id = $product_id ? ($inventoryMap[$product_id] ?? null) : null;
            if (! $inventory_id) {
                throw new Exception('Inventory not found for service ID: '.$value->spa_service_id);
            }
            $data['items'][] = [
                'inventory_id' => $inventory_id,
                'employee_id' => $employeeMap[$value->employee_id] ?? null,
                'product_id' => $product_id,
                'unit_id' => $product?->unit_id,
                'unit_price' => $value->unit_price,
                'quantity' => $value->quantity,
                'gross_total' => $value->unit_price * $value->quantity,
                'discount' => $value->discount,
                'tax' => 0,
                'total' => $value->unit_price * $value->quantity,
            ];
        }

        foreach ($itemsBySale[$sale->id] ?? [] as $value) {
            $product = $productMap[$value->product_id] ?? null;
            $product_id = $product?->id;
            $inventory_id = $product_id ? ($inventoryMap[$product_id] ?? null) : null;

            $data['items'][] = [
                'inventory_id' => $inventory_id,
                'employee_id' => $employeeMap[$value->employee_id] ?? null,
                'product_id' => $product_id,
                'unit_id' => $product?->unit_id,
                'unit_price' => $value->unit_price,
                'quantity' => $value->total_quantity,
                'net_amount' => $value->unit_price * $value->total_quantity,
                'discount' => $value->total_discount,
                'tax' => 0,
                'total' => ($value->unit_price * $value->total_quantity) - $value->total_discount,
            ];
        }

        // Order line items by inventory_id so every sale mutates inventory rows in the same ascending
        // order. Consistent lock ordering across concurrent transactions is what prevents the bulk of
        // worker-vs-worker deadlocks; item order does not affect sale totals. Nulls sort first.
        usort($data['items'], fn ($a, $b) => ($a['inventory_id'] ?? 0) <=> ($b['inventory_id'] ?? 0));

        $data['items'] = collect($data['items']);

        $data['gross_amount'] = $data['items']->sum('net_amount');
        $data['item_discount'] = $data['items']->sum('discount');
        $data['total_quantity'] = $data['items']->sum('quantity');
        $data['total'] = $data['items']->sum('total');

        $data['payments'] = [];
        foreach ($journalsBySale[$sale->id] ?? [] as $value) {
            $data['payments'][] = [
                'payment_method_id' => $accountMap[$value->debit] ?? null,
                'amount' => $value->amount,
            ];
        }

        return $data;
    }

    /**
     * Build (once per worker process, per tenant) the in-memory lookup maps the replay needs.
     *
     * @return array{0:Collection,1:Collection,2:Collection,3:Collection,4:Collection,5:Collection}
     */
    protected function maps(): array
    {
        if (isset(self::$maps[$this->tenantId])) {
            return self::$maps[$this->tenantId];
        }

        $accountMap = Account::pluck('id', 'second_reference_no');            // customer + payment-method accounts
        $userMap = User::where('type', 'user')->pluck('id', 'second_reference_no');
        $employeeMap = User::where('type', 'employee')->pluck('id', 'second_reference_no');
        $productMap = Product::where('type', 'product')->get(['id', 'unit_id', 'second_reference_no'])->keyBy('second_reference_no');
        $serviceMap = Product::where('type', 'service')->get(['id', 'unit_id', 'second_reference_no'])->keyBy('second_reference_no');
        // product_id => inventory_id. orderByDesc so the LOWEST id wins the final overwrite, matching
        // the original Inventory::where('product_id', ...)->value('id') (first row). Single branch, so
        // there is one inventory row per product anyway.
        $inventoryMap = Inventory::orderByDesc('id')->pluck('id', 'product_id');

        return self::$maps[$this->tenantId] = [$accountMap, $userMap, $employeeMap, $productMap, $serviceMap, $inventoryMap];
    }

    /**
     * Prime the branch/account lookup caches the journal actions depend on, if a worker started
     * before MigrateDataCommand primed them. Mirrors MigrateDataCommand::refreshLookupCaches().
     */
    protected function ensureLookupCaches(): void
    {
        if (Cache::has('accounts_slug_id_map') && Cache::has('branches')) {
            return;
        }

        Cache::put('branches', Branch::select('id', 'name')->get(), now()->addYear());
        Cache::put('accounts_slug_id_map', DB::table('accounts')->where('is_locked', 1)->pluck('id', 'slug')->toArray(), now()->addYear());
    }
}
