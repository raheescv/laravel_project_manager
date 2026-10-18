<?php

use App\Livewire\Purchase\ColumnVisibility;
use App\Livewire\Purchase\Table as PurchaseTable;
use App\Livewire\PurchaseReturn\Table as PurchaseReturnTable;
use App\Models\Configuration;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * A tenant that never saved its purchase column choices has no
 * `purchase_visible_column` row; the lists must fall back to the defaults.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('renders the purchase and purchase return lists with no saved column choices', function (string $component): void {
    expect(Configuration::where('key', 'purchase_visible_column')->exists())->toBeFalse();

    Livewire::test($component)
        ->assertOk()
        ->assertSet('purchase_visible_column.vendor', true);
})->with([
    'purchase list' => [PurchaseTable::class],
    'purchase return list' => [PurchaseReturnTable::class],
]);

it('keeps saved choices and fills in columns saved before they existed', function (): void {
    Configuration::create([
        'tenant_id' => $this->world->tenant->id,
        'key' => 'purchase_visible_column',
        'value' => json_encode(['vendor' => false]),
    ]);

    $columns = ColumnVisibility::visibleColumns();

    expect($columns['vendor'])->toBeFalse()
        ->and($columns['grand_total'])->toBeTrue();
});
