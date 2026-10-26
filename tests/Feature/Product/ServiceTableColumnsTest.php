<?php

use App\Livewire\Service\Table;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The service list shows each service's priority and lets every user pick
 * which columns are visible, remembered per user like the account list.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->world->product->update(['type' => 'service', 'priority' => 7, 'name' => 'Deep Tissue Massage']);
});

it('shows the priority column for services', function (): void {
    Livewire::test(Table::class)
        ->assertSet('columns.priority', true)
        ->assertSee('Priority')
        ->assertSeeHtml('fa-sort-numeric-asc me-1 opacity-50"></i>7');
});

it('hides a column and remembers the choice for the user', function (): void {
    Livewire::test(Table::class)
        ->set('columns.priority', false)
        ->assertDontSeeHtml('fa-sort-numeric-asc me-1 opacity-50"></i>7');

    expect(UserPreference::getValue('service.table.columns')['priority'])->toBeFalse();

    Livewire::test(Table::class)
        ->assertSet('columns.priority', false)
        ->assertSet('columns.name', true);
});

it('restores the default columns on reset', function (): void {
    Livewire::test(Table::class)
        ->set('columns.priority', false)
        ->set('columns.code', false)
        ->call('resetColumns')
        ->assertSet('columns.priority', true)
        ->assertSet('columns.code', true);
});
