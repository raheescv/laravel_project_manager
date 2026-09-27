<?php

use App\Models\Configuration;
use App\Models\Inventory;
use App\Models\Scopes\AssignedBranchScope;
use App\Support\TenantCache;
use Tests\Support\PosWorld;

/**
 * inventory:sync-barcodes re-applies Settings -> "Barcode Type" to the
 * inventory rows that already exist.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->second = $this->world->addBranch();
});

function sibSetBarcodeType(PosWorld $world, string $type): void
{
    Configuration::withoutTenant()->updateOrCreate(
        ['tenant_id' => $world->tenant->id, 'key' => 'barcode_type'],
        ['value' => $type],
    );
    TenantCache::forget('barcode_type');
}

function sibInventories(PosWorld $world)
{
    return Inventory::withoutGlobalScope(AssignedBranchScope::class)
        ->where('product_id', $world->product->id)
        ->orderBy('id')
        ->get();
}

it('copies the product barcode onto every inventory row when product wise', function (): void {
    sibSetBarcodeType($this->world, 'product_wise');
    $this->world->product->update(['barcode_number' => '777001']);

    $this->artisan('inventory:sync-barcodes', ['--tenant' => $this->world->tenant->id])->assertSuccessful();

    expect(sibInventories($this->world)->pluck('barcode_number')->unique()->values()->all())->toBe(['777001']);
});

it('gives each inventory row its own barcode when system generated', function (): void {
    sibSetBarcodeType($this->world, 'system_generation');
    Inventory::withoutGlobalScope(AssignedBranchScope::class)
        ->where('product_id', $this->world->product->id)
        ->update(['barcode_number' => '555001']);

    $this->artisan('inventory:sync-barcodes', ['--tenant' => $this->world->tenant->id])->assertSuccessful();

    $barcodes = sibInventories($this->world)->pluck('barcode_number');
    expect($barcodes->first())->toBe('555001')
        ->and($barcodes->unique())->toHaveCount($barcodes->count());
});

it('changes nothing on a dry run', function (): void {
    sibSetBarcodeType($this->world, 'product_wise');
    $this->world->product->update(['barcode_number' => '777002']);
    $before = sibInventories($this->world)->pluck('barcode_number')->all();

    $this->artisan('inventory:sync-barcodes', ['--tenant' => $this->world->tenant->id, '--dry-run' => true])->assertSuccessful();

    expect(sibInventories($this->world)->pluck('barcode_number')->all())->toBe($before);
});
