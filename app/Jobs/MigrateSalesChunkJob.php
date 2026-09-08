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
use App\Support\Migration\SourceTimestamps;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
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

        // Replayed sales carry their own historical date: keep Sale::creating from binding them to
        // whatever till session is open right now, which would re-date them and pollute that
        // session's cash reconciliation.
        BulkImport::enableHistoricalReplay();

        // Queue workers have no request, so the tenant global scope would otherwise resolve to null
        // and every scoped read/write would target the wrong rows. Pin it explicitly for this job.
        $tenant = Tenant::find($this->tenantId);
        if ($tenant) {
            app(TenantService::class)->setCurrentTenant($tenant);
        }

        // The command disables FK checks for the whole migration on its own DB session; the worker
        // runs in a separate session, so disable them here too to match the inline replay's behaviour.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // The journal-entry actions resolve branch/account ids through the tenant-keyed
        // lazy caches (tenant_cache('branches') / Account::slugIdMap()); with the tenant
        // pinned above they self-populate on first use, so no manual priming is needed.

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

        // `discount` is part of the grouping key, not a SUM: the old schema stores it PER UNIT
        // (see buildSaleData), so adding it up across merged rows would be meaningless, and two
        // rows of the same product at the same net price but different discounts describe
        // different list prices and must stay apart.
        $itemsBySale = DB::connection('mysql2')
            ->table('sale_items')
            ->select(
                'sale_id',
                'product_id',
                'employee_id',
                'unit_price',
                'discount',
                DB::raw('SUM(quantity) as total_quantity')
            )
            ->whereNull('deleted_at')
            ->whereIn('sale_id', $this->saleIds)
            ->groupBy('sale_id', 'product_id', 'employee_id', 'unit_price', 'discount')
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

                $this->createSaleWithRetry($data, $sale, $created, $failed);
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
     *
     * @param  object  $source  The mysql2 sales row being replayed (its id and original timestamps).
     */
    protected function createSaleWithRetry(array $data, $source, int &$created, int &$failed): void
    {
        $maxAttempts = 5;

        for ($attempt = 1; ; $attempt++) {
            try {
                $ok = DB::transaction(function () use ($data, $source): bool {
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

                    // The action stamps created_at/updated_at with now(); put the source sale's own
                    // timestamps back so the replayed row keeps its place in history.
                    SourceTimestamps::apply($response['data'], $source);

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
                Log::error('Sales migration error (sale_id '.$source->id.') after '.$attempt.' attempt(s): '.$e->getMessage());

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
     *
     * Line pricing is deliberately re-expressed, because the two schemas mean opposite things by the
     * same column names. The old one stores a PER-UNIT discount already taken off the rate:
     *
     *     unit_price = mrp - discount        total = unit_price * quantity
     *
     * The new sale_items store the GROSS rate with a LINE discount, and derive the money in stored
     * generated columns (so any 'total'/'net_amount' passed in here would be ignored anyway):
     *
     *     gross_amount = unit_price * quantity      net_amount = gross_amount - discount
     *
     * Replaying the old numbers verbatim therefore charged every discount twice - a 1,500 service
     * sold for 1,000 landed as 1,000 gross less 500, i.e. a 500 sale - and because sales.grand_total
     * is generated from the items, the sale total was wrong too. Mapping the rate back up to the
     * list price (unit_price + discount, which equals the old mrp on every source row) and scaling
     * the discount to the line reproduces the original net exactly.
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
            // Sign flip, not a copy. The old schema SUBTRACTS its round_off (grand_total = total -
            // other_discount - round_off, and every stored value is <= 0: it holds the fraction that
            // was knocked off); the new grand_total is generated as total - other_discount + freight
            // + round_off. Carried across verbatim - or dropped, as it was - the rounding is lost and
            // the sale under-totals by that fraction: 275.00 less 37.50 settles at 237.50 where the
            // original invoice was rounded to, and paid as, 238.00.
            'round_off' => -($sale->round_off ?? 0),
            // Not part of grand_total in either schema (it is money on top, recorded for the record).
            'tip' => $sale->tip ?? 0,
            'paid' => $sale->paid ? $sale->paid : 0,
            'address' => null,
            'status' => $sale->status == 2 ? 'completed' : 'draft',
            'source' => 'migration',
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
                'unit_price' => $value->unit_price + $value->discount,
                'quantity' => $value->quantity,
                'discount' => $value->discount * $value->quantity,
                'tax' => 0,
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
                'unit_price' => $value->unit_price + $value->discount,
                'quantity' => $value->total_quantity,
                'discount' => $value->discount * $value->total_quantity,
                'tax' => 0,
            ];
        }

        // Order line items by inventory_id so every sale mutates inventory rows in the same ascending
        // order. Consistent lock ordering across concurrent transactions is what prevents the bulk of
        // worker-vs-worker deadlocks; item order does not affect sale totals. Nulls sort first.
        usort($data['items'], fn ($a, $b) => ($a['inventory_id'] ?? 0) <=> ($b['inventory_id'] ?? 0));

        $data['items'] = collect($data['items']);

        // Only these two are stored on the sale: total, grand_total and balance are generated from
        // them (plus other_discount/freight/round_off), so the header's own copies are ignored.
        // SaleCreateAction re-derives both from the created items; seeding them here just keeps the
        // inserted row correct from the start.
        $data['gross_amount'] = $data['items']->sum(fn ($item) => $item['unit_price'] * $item['quantity']);
        $data['item_discount'] = $data['items']->sum('discount');

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
}
