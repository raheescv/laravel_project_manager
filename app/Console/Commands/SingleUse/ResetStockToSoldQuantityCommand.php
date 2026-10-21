<?php

namespace App\Console\Commands\SingleUse;

use App\Actions\Product\Inventory\UpdateAction as InventoryUpdateAction;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\SaleItem;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Custom one-off correction: sets the stock of a tenant's inventory rows (one
 * branch, or every branch when none is given) as if they started from 0 and
 * every sale was deducted: quantity = 0 - sold (completed, non-deleted sales,
 * in base units), so a row with 3 sold ends at -3.
 *
 * Only inventory rows that appear on a sale are touched; everything else keeps
 * its quantity. Each change goes through the inventory
 * UpdateAction so it is logged like any other stock movement.
 */
class ResetStockToSoldQuantityCommand extends Command
{
    protected $signature = 'inventory:reset-to-sold
                            {tenant : Tenant id whose inventory is reset}
                            {branch? : Branch id to reset (default: every branch of the tenant)}
                            {--from= : Only count sales dated on/after this date (Y-m-d)}
                            {--to= : Only count sales dated on/before this date (Y-m-d)}
                            {--dry-run : Report what would change without saving}';

    protected $description = 'Reset branch inventory quantity to 0 minus the quantity sold from it';

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

        $changesByBranch = $branches->mapWithKeys(fn (Branch $branch): array => [$branch->id => $this->changesFor($branch)]);

        foreach ($branches as $branch) {
            $changes = $changesByBranch[$branch->id];
            $this->line("{$tenant->name} / {$branch->name}");
            $this->table(
                ['Inventory', 'Product ID', 'Code', 'Product Name', 'Current', 'New (0 − Sold)'],
                $changes->map(fn (array $change): array => [
                    $change['inventory']->id,
                    $change['inventory']->product_id,
                    $change['inventory']->product?->code,
                    $change['inventory']->product?->name,
                    $change['from'],
                    $change['to'],
                ])->all(),
            );
        }

        $total = $changesByBranch->sum(fn (Collection $changes): int => $changes->count());

        if ($this->option('dry-run')) {
            $this->info("{$tenant->name}: would reset {$total} inventory row(s) across {$branches->count()} branch(es).");

            return self::SUCCESS;
        }

        $userId = User::systemUserId($tenant->id);

        try {
            DB::transaction(function () use ($changesByBranch, $userId): void {
                foreach ($changesByBranch->flatten(1) as $change) {
                    $inventory = $change['inventory'];
                    $data = $inventory->toArray();
                    $data['quantity'] = $change['to'];
                    $data['remarks'] = 'Stock Reset: to sold quantity';
                    $data['model'] = 'StockReset';
                    $data['model_id'] = null;
                    $data['updated_by'] = $userId ?? $inventory->updated_by;

                    $response = (new InventoryUpdateAction())->execute($data, $inventory->id);
                    if (! $response['success']) {
                        throw new Exception("Inventory ID {$inventory->id}: {$response['message']}");
                    }
                }
            });
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("{$tenant->name}: reset {$total} inventory row(s) across {$branches->count()} branch(es) to sold quantity.");

        return self::SUCCESS;
    }

    /**
     * Inventory rows in the branch whose quantity differs from their sold quantity.
     *
     * @return Collection<int, array{inventory: Inventory, from: float, to: float}>
     */
    protected function changesFor(Branch $branch): Collection
    {
        $soldQuantities = $this->soldQuantities($branch);
        $inventories = Inventory::withoutGlobalScope(AssignedBranchScope::class)
            ->where('branch_id', $branch->id)
            ->whereIn('id', $soldQuantities->keys())
            ->with('product:id,code,name')
            ->orderBy('id')
            ->get();

        return $inventories
            ->map(fn (Inventory $inventory): array => [
                'inventory' => $inventory,
                'from' => (float) $inventory->quantity,
                'to' => 0 - (float) $soldQuantities[$inventory->id],
            ])
            ->reject(fn (array $change): bool => $change['from'] === $change['to'])
            ->values();
    }

    /**
     * Sold base-unit quantity per inventory id for the branch's completed sales.
     *
     * @return Collection<int, float>
     */
    protected function soldQuantities(Branch $branch): Collection
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.branch_id', $branch->id)
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->when($this->option('from'), fn ($query, $from) => $query->whereDate('sales.date', '>=', $from))
            ->when($this->option('to'), fn ($query, $to) => $query->whereDate('sales.date', '<=', $to))
            ->groupBy('sale_items.inventory_id')
            ->selectRaw('sale_items.inventory_id, SUM(sale_items.base_unit_quantity) as sold_quantity')
            ->pluck('sold_quantity', 'inventory_id')
            ->map(fn ($quantity): float => (float) $quantity);
    }
}
