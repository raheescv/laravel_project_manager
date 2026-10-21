<?php

namespace App\Console\Commands\SingleUse;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\ProductRawMaterial;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Custom one-off recovery: recreates the "Sale:<invoice>" inventory log rows of
 * completed sales whose logs were deleted.
 *
 * A sale line is restored only when its sale has fewer stock-out logs for that
 * product than it has lines, so the command is safe to re-run. Composite
 * products log their raw materials, as StockUpdateAction does. The row is dated
 * to the sale date at the sale's time of day (as LogInventoryAction does) and
 * its balance continues from the previous log of that branch and product, or
 * from 0 when there is none.
 */
class RestoreSaleInventoryLogsCommand extends Command
{
    protected $signature = 'inventory-log:restore-sales
                            {tenant : Tenant id whose sale logs are restored}
                            {branch? : Branch id to restore (default: every branch of the tenant)}
                            {--from= : Only sales dated on/after this date (Y-m-d)}
                            {--to= : Only sales dated on/before this date (Y-m-d)}
                            {--dry-run : Report what would be restored without saving}';

    protected $description = 'Recreate missing inventory log rows for completed sales';

    /** @var array<string, array{at: string, balance: float}> Last restored row per branch/product, for chaining balances. */
    protected array $lastRestored = [];

    /** @var array<int, string> */
    protected array $userNames = [];

    public function handle(TenantService $tenantService): int
    {
        $tenant = Tenant::find($this->argument('tenant'));
        if (! $tenant) {
            $this->error('Tenant not found.');

            return self::FAILURE;
        }

        $branches = Branch::withTenant($tenant->id)
            ->when($this->argument('branch'), fn ($query, $branchId) => $query->where('id', $branchId))
            ->orderBy('id')
            ->get();
        if ($branches->isEmpty()) {
            $this->error("Branch not found for tenant {$tenant->id}.");

            return self::FAILURE;
        }
        $tenantService->setCurrentTenant($tenant);

        $movements = $this->missingMovements($branches->pluck('id')->all());

        $this->table(
            ['Invoice', 'Date', 'Branch', 'Product ID', 'Product', 'Qty Out', 'Balance'],
            $movements->map(fn (array $movement): array => [
                $movement['remarks'],
                $movement['at'],
                $branches->firstWhere('id', $movement['inventory']->branch_id)?->name,
                $movement['inventory']->product_id,
                $movement['inventory']->product?->name,
                $movement['quantity'],
                $movement['balance'],
            ])->all(),
        );

        if ($this->option('dry-run')) {
            $this->info("{$tenant->name}: would restore {$movements->count()} sale inventory log(s).");

            return self::SUCCESS;
        }

        DB::transaction(function () use ($movements): void {
            foreach ($movements as $movement) {
                $this->restore($movement);
            }
        });

        $this->info("{$tenant->name}: restored {$movements->count()} sale inventory log(s).");

        return self::SUCCESS;
    }

    /**
     * Stock-out movements of completed sale lines that have no inventory log, in
     * date order, each with the balance it would have carried.
     *
     * @param  array<int, int>  $branchIds
     * @return Collection<int, non-empty-array<string, mixed>>
     */
    protected function missingMovements(array $branchIds): Collection
    {
        $items = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.branch_id', $branchIds)
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->when($this->option('from'), fn ($query, $from) => $query->whereDate('sales.date', '>=', $from))
            ->when($this->option('to'), fn ($query, $to) => $query->whereDate('sales.date', '<=', $to))
            ->orderBy('sales.date')
            ->orderBy('sales.created_at')
            ->orderBy('sale_items.id')
            ->toBase()
            ->get([
                'sale_items.id',
                'sale_items.inventory_id',
                'sale_items.product_id',
                'sale_items.base_unit_quantity',
                'sales.id as sale_id',
                'sales.branch_id',
                'sales.invoice_no',
                'sales.date as sale_date',
                'sales.created_at as sale_created_at',
                'sales.created_by as sale_created_by',
            ]);

        $rawMaterials = ProductRawMaterial::whereIn('product_id', $items->pluck('product_id')->unique())->get()->groupBy('product_id');

        $movements = collect();
        foreach ($items as $item) {
            $at = Carbon::parse($item->sale_date)->format('Y-m-d').' '.Carbon::parse($item->sale_created_at)->format('H:i:s');
            $base = [
                'sale_id' => (int) $item->sale_id,
                'user_id' => (int) $item->sale_created_by,
                'at' => $at,
            ];

            if ($rawMaterials->has($item->product_id)) {
                foreach ($rawMaterials[$item->product_id] as $rawMaterial) {
                    $movements->push($base + [
                        'inventory_id' => null,
                        'branch_id' => (int) $item->branch_id,
                        'product_id' => (int) $rawMaterial->raw_material_id,
                        'quantity' => (float) $rawMaterial->quantity * (float) $item->base_unit_quantity,
                        'remarks' => 'Sale (Raw Material):'.$item->invoice_no,
                    ]);
                }

                continue;
            }

            $movements->push($base + [
                'inventory_id' => (int) $item->inventory_id,
                'branch_id' => (int) $item->branch_id,
                'product_id' => (int) $item->product_id,
                'quantity' => (float) $item->base_unit_quantity,
                'remarks' => 'Sale:'.$item->invoice_no,
            ]);
        }

        $loggedCounts = InventoryLog::query()
            ->where('model', 'Sale')
            ->whereIn('model_id', $movements->pluck('sale_id')->unique())
            ->where('quantity_out', '>', 0)
            ->selectRaw('model_id, product_id, COUNT(*) as logged')
            ->groupBy('model_id', 'product_id')
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->model_id.':'.$row->product_id => (int) $row->logged]);

        return $movements
            ->groupBy(fn (array $movement): string => $movement['sale_id'].':'.$movement['product_id'])
            ->flatMap(fn (Collection $group, string $key): Collection => $group->slice(min($group->count(), $loggedCounts[$key] ?? 0)))
            ->sortBy('at')
            ->values()
            ->map(fn (array $movement): ?array => $this->withInventoryAndBalance($movement))
            ->filter()
            ->values();
    }

    /**
     * Attach the inventory row the movement hits and the balance after it.
     *
     * @param  array<string, mixed>  $movement
     * @return array<string, mixed>|null
     */
    protected function withInventoryAndBalance(array $movement): ?array
    {
        $inventory = Inventory::withoutGlobalScopes()
            ->withTrashed()
            ->with('product:id,name')
            ->when(
                $movement['inventory_id'],
                fn ($query, $inventoryId) => $query->where('id', $inventoryId),
                fn ($query) => $query->where('product_id', $movement['product_id'])->where('branch_id', $movement['branch_id'])->whereNull('employee_id'),
            )
            ->first();

        if (! $inventory) {
            $this->warn("Skipped {$movement['remarks']}: no inventory row for product {$movement['product_id']}.");

            return null;
        }

        $key = $inventory->branch_id.':'.$inventory->product_id;
        $previous = InventoryLog::query()
            ->where('branch_id', $inventory->branch_id)
            ->where('product_id', $inventory->product_id)
            ->where('created_at', '<=', $movement['at'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first(['balance', 'created_at']);

        $previousBalance = (float) ($previous->balance ?? 0);
        $restored = $this->lastRestored[$key] ?? null;
        if ($restored && (! $previous || $restored['at'] >= $previous->created_at->format('Y-m-d H:i:s'))) {
            $previousBalance = $restored['balance'];
        }

        $balance = round($previousBalance - $movement['quantity'], 3);
        $this->lastRestored[$key] = ['at' => $movement['at'], 'balance' => $balance];

        return $movement + ['inventory' => $inventory, 'balance' => $balance];
    }

    /** @param  array<string, mixed>  $movement */
    protected function restore(array $movement): void
    {
        $inventory = $movement['inventory'];

        $log = new InventoryLog([
            'branch_id' => $inventory->branch_id,
            'employee_id' => $inventory->employee_id,
            'product_id' => $inventory->product_id,
            'quantity_in' => 0,
            'quantity_out' => $movement['quantity'],
            'balance' => $movement['balance'],
            'barcode' => $inventory->barcode ?? '',
            'batch' => $inventory->batch ?? '',
            'cost' => $inventory->cost ?? 0,
            'remarks' => $movement['remarks'],
            'model' => 'Sale',
            'model_id' => $movement['sale_id'],
            'user_id' => $movement['user_id'],
            'user_name' => $this->userName($movement['user_id']),
        ]);
        $log->created_at = $movement['at'];
        $log->updated_at = $movement['at'];
        $log->save();
    }

    protected function userName(int $userId): string
    {
        return $this->userNames[$userId] ??= (string) User::withoutGlobalScopes()->whereKey($userId)->value('name');
    }
}
