<?php

use App\Livewire\InventoryTransfer\Page;
use App\Models\Inventory;
use App\Support\TenantCache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create(stock: 12);
    $this->toBranch = $this->world->addBranch('Doha Branch', 'DB');
    TenantCache::forget('branches');
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id, 'branch_name' => $this->world->branch->name]);
});

it('shows the stock of both the from and to branch for each item', function (): void {
    $fromInventoryId = Inventory::query()
        ->where('product_id', $this->world->product->id)
        ->where('branch_id', $this->world->branch->id)
        ->value('id');

    $component = Livewire::test(Page::class)
        ->call('selectItem', $fromInventoryId)
        ->assertSee('Main Branch Stock')
        ->assertSee('To Branch Stock')
        ->assertSeeHtml('<td class="text-end">-</td>')
        ->set('inventory_transfers.to_branch_id', $this->toBranch->id)
        ->assertSee('Doha Branch Stock')
        ->assertSeeHtml('<td class="text-end">12</td>')
        ->assertSeeHtml('<td class="text-end">100</td>');

    expect($component->instance()->toBranchStocks())->toBe([$this->world->product->id => 100.0]);
});
