<?php

use App\Livewire\Product\Table;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The product list's floating bulk bar: shown only with a selection, and able
 * to flip the "is selling" flag on every selected product at once.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('shows the floating bulk bar only when products are selected', function (): void {
    Livewire::test(Table::class)
        ->assertDontSeeHtml('product-bulk-bar"')
        ->set('selected', [$this->world->product->id])
        ->assertSeeHtml('class="product-bulk-bar"')
        ->assertSee('Mark as not selling');
});

it('marks the selected products as not selling and back', function (): void {
    $product = $this->world->product;
    $product->update(['is_selling' => true]);

    Livewire::test(Table::class)
        ->set('selected', [$product->id])
        ->call('updateSelling', false)
        ->assertSet('selected', [])
        ->assertDispatched('success');

    expect((bool) $product->fresh()->is_selling)->toBeFalse();

    Livewire::test(Table::class)
        ->set('selected', [$product->id])
        ->call('updateSelling', true);

    expect((bool) $product->fresh()->is_selling)->toBeTrue();
});

it('refuses a bulk selling update with nothing selected', function (): void {
    Livewire::test(Table::class)
        ->call('updateSelling', false)
        ->assertDispatched('error');

    expect((bool) Product::find($this->world->product->id)->is_selling)->toBeTrue();
});

it('no longer offers delete in the top toolbar', function (): void {
    Livewire::test(Table::class)
        ->assertDontSeeHtml('title="Delete Selected"');
});
