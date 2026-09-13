<?php

use App\Livewire\Property\PropertyLead\Table;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The long lead list filters are TomSelect controls behind wire:ignore, so the
 * server-rendered markup must already carry the current value — TomSelect reads
 * the selected option once, and Livewire never re-renders inside the wrapper.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

it('renders the long lead filters as TomSelect controls holding the current value', function (): void {
    Livewire::withQueryParams(['status' => 'Follow Up'])
        ->test(Table::class)
        ->assertSeeHtml('id="leadFilterStatus"')
        ->assertSeeHtml('id="leadFilterSource"')
        ->assertSeeHtml('id="leadFilterGroup"')
        ->assertSeeHtml('id="leadFilterAssigned"')
        ->assertSeeHtml('<option value="Follow Up" selected')
        ->assertDontSeeHtml('wire:model.live="filterStatus"')
        ->assertDontSeeHtml('wire:model.live="filterAssignedTo"');
});
