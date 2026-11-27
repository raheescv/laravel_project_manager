<?php

use App\Livewire\Product\Page;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The product edit page's tabs are real links (?tab=Variants) so a tab can be
 * bookmarked, shared, opened in a new browser tab and survives a reload.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('renders each tab as a ?tab= link', function (): void {
    Livewire::test(Page::class, ['type' => 'product', 'table_id' => $this->world->product->id])
        ->assertSet('selectedTab', 'Prices')
        ->assertSeeHtml('href="?tab=Prices"')
        ->assertSeeHtml('href="?tab=Variants"')
        ->assertSeeHtml('href="?tab=Images"');
});

it('opens the tab named in the query string', function (): void {
    Livewire::withQueryParams(['tab' => 'Variants'])
        ->test(Page::class, ['type' => 'product', 'table_id' => $this->world->product->id])
        ->assertSet('selectedTab', 'Variants');
});

it('falls back to the default tab for an unknown or hidden tab', function (string $tab): void {
    Livewire::withQueryParams(['tab' => $tab])
        ->test(Page::class, ['type' => 'product', 'table_id' => $this->world->product->id])
        ->assertSet('selectedTab', 'Prices');
})->with(['Nonsense', 'Depreciation', 'Audit']);

it('keeps the selected tab when switching and when the page refreshes', function (): void {
    Livewire::test(Page::class, ['type' => 'product', 'table_id' => $this->world->product->id])
        ->call('tabSelect', 'Stock')
        ->assertSet('selectedTab', 'Stock')
        ->call('refresh')
        ->assertSet('selectedTab', 'Stock')
        ->call('tabSelect', 'Bogus')
        ->assertSet('selectedTab', 'Prices');
});
