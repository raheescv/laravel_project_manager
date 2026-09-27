<?php

namespace App\Console\Commands\Product;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\Tenant;
use App\Services\TenantService;
use App\Support\TenantCache;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Re-applies Settings -> Product Configuration -> "Barcode Type" to the
 * inventory rows that already exist, so switching the setting is not only for
 * products created afterwards.
 *
 * - product_wise: every inventory row carries its product's barcode (a product
 *   without one is given a fresh barcode first).
 * - system_generation: every inventory row has its own barcode. Rows with no
 *   barcode, and all but the oldest row sharing a barcode, get a new one.
 *   --all regenerates every row instead.
 */
class SyncInventoryBarcodesCommand extends Command
{
    protected $signature = 'inventory:sync-barcodes
                            {--tenant= : Only this tenant id (default: every active tenant)}
                            {--all : system_generation only - regenerate every inventory barcode, not only missing/duplicate ones}
                            {--dry-run : Report what would change without saving}';

    protected $description = 'Regenerate inventory barcodes according to each tenant\'s "Barcode Type" setting';

    public function handle(TenantService $tenantService): int
    {
        $tenants = Tenant::query()
            ->when($this->option('tenant'), fn ($query, $tenantId) => $query->where('id', $tenantId))
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->error('No active tenant found.');

            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $tenantService->setCurrentTenant($tenant);
            TenantCache::forget('barcode_type');
            TenantCache::forget('barcode_prefix');

            $barcodeType = tenant_cache('barcode_type', '');

            $updated = match ($barcodeType) {
                'product_wise' => $this->syncProductWise(),
                'system_generation' => $this->syncSystemGenerated(),
                default => null,
            };

            if ($updated === null) {
                $this->warn("· {$tenant->name}: Barcode Type is not set - skipped.");

                continue;
            }

            $verb = $this->option('dry-run') ? 'would update' : 'updated';
            $this->info("✓ {$tenant->name} ({$barcodeType}): {$verb} {$updated} inventory barcode(s).");
        }

        return self::SUCCESS;
    }

    /** Give every inventory row its product's barcode. */
    protected function syncProductWise(): int
    {
        $updated = 0;

        $this->inventoryQuery()
            ->with('product:id,type,barcode_prefix,barcode_number')
            ->chunkById(500, function (Collection $inventories) use (&$updated): void {
                foreach ($inventories as $inventory) {
                    $product = $inventory->product;
                    if (! $product) {
                        continue;
                    }

                    if (blank($product->barcode_number) && $product->type === 'product') {
                        $this->assignProductBarcode($product);
                    }

                    $barcodeNumber = $product->barcode_number;
                    if (blank($barcodeNumber)) {
                        $barcodeNumber = filled($inventory->barcode_number) ? null : $this->nextBarcode();
                    }

                    if ($barcodeNumber !== null && $barcodeNumber !== $inventory->barcode_number) {
                        $this->saveBarcode($inventory, $barcodeNumber);
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    /** Give every inventory row a barcode of its own. */
    protected function syncSystemGenerated(): int
    {
        $regenerateAll = (bool) $this->option('all');
        $seen = [];
        $updated = 0;

        $this->inventoryQuery()->chunkById(500, function (Collection $inventories) use (&$seen, &$updated, $regenerateAll): void {
            foreach ($inventories as $inventory) {
                $barcodeNumber = (string) $inventory->barcode_number;
                $needsBarcode = $regenerateAll || $barcodeNumber === '' || isset($seen[$barcodeNumber]);
                $seen[$barcodeNumber] = true;

                if ($needsBarcode) {
                    $this->saveBarcode($inventory, $this->nextBarcode());
                    $updated++;
                }
            }
        });

        return $updated;
    }

    /** The next barcode from the tenant counter; a dry run never consumes one. */
    protected function nextBarcode(): string
    {
        return $this->option('dry-run') ? '(new)' : generateBarcode();
    }

    protected function inventoryQuery(): Builder
    {
        return Inventory::withoutGlobalScope(AssignedBranchScope::class)->orderBy('id');
    }

    protected function assignProductBarcode(Product $product): void
    {
        $product->barcode_number = $this->nextBarcode();
        if (! $this->option('dry-run')) {
            $product->save();
        }
    }

    protected function saveBarcode(Inventory $inventory, string $barcodeNumber): void
    {
        if ($this->option('dry-run')) {
            return;
        }

        $inventory->barcode_number = $barcodeNumber;
        $inventory->save();
    }
}
