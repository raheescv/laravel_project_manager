<?php

use App\Livewire\Product\Page;
use App\Models\Unit;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * A new product's Base Unit starts on "Nos", whatever unit happens to be first.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('defaults the base unit to Nos even when another unit was created first', function (): void {
    $nos = Unit::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Nos', 'code' => 'Nos']);

    expect(Unit::defaultBaseUnit()->id)->toBe($nos->id);

    Livewire::test(Page::class, ['type' => 'product'])
        ->assertSet('products.unit_id', $nos->id);
});

it('falls back to the oldest unit when the tenant has no Nos', function (): void {
    expect(Unit::defaultBaseUnit()->id)->toBe($this->world->product->unit_id);
});
