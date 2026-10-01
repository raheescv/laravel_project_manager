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

it('stamps reassigned_at only when the salesman changes', function (): void {
    $other = \App\Models\User::factory()->create();
    $lead = \App\Models\PropertyLead::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'name' => 'Reassign me',
        'type' => 'Sales',
        'source' => 'Walk-In',
        'status' => 'New Lead',
        'assigned_to' => $this->world->user->id,
    ]);

    expect($lead->reassigned_at)->toBeNull();

    $lead->update(['status' => 'Follow Up']);
    expect($lead->fresh()->reassigned_at)->toBeNull();

    $this->travelTo(now()->addDay());
    $lead->update(['assigned_to' => $other->id]);
    expect($lead->fresh()->reassigned_at->toDateString())->toBe(now()->toDateString());
});

it('filters the list on the chosen date basis', function (): void {
    $make = fn (string $name, array $dates) => \App\Models\PropertyLead::forceCreate([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'name' => $name,
        'type' => 'Sales',
        'source' => 'Walk-In',
        'status' => 'New Lead',
        ...$dates,
    ]);
    $make('Old but reassigned', ['created_at' => now()->subMonths(3), 'updated_at' => now()->subMonths(3), 'reassigned_at' => now()->subDay()]);
    $make('Fresh lead', ['created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

    \Livewire\Livewire::test(\App\Livewire\Property\PropertyLead\Table::class)
        ->assertSee('Fresh lead')
        ->assertDontSee('Old but reassigned')
        ->set('dateField', 'reassigned')
        ->assertSee('Old but reassigned')
        ->assertDontSee('Fresh lead');
});

it('filters by sub source, sub status and nationality', function (): void {
    $make = fn (string $name, array $attributes) => \App\Models\PropertyLead::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'name' => $name,
        'type' => 'Sales',
        'source' => 'Facebook',
        'status' => 'Follow Up',
        ...$attributes,
    ]);
    $country = \App\Models\Country::query()->first() ?? \App\Models\Country::create(['name' => 'Qatar']);
    $make('Ad A lead', ['sub_source' => 'Ad A', 'sub_status' => 'Call again', 'country_id' => $country->id]);
    $make('Ad B lead', ['sub_source' => 'Ad B']);

    \Livewire\Livewire::test(\App\Livewire\Property\PropertyLead\Table::class)
        ->assertSeeHtml('id="leadFilterCountry"')
        ->set('filterSource', 'Facebook')
        ->assertViewHas('subSources', ['Ad A' => 'Ad A', 'Ad B' => 'Ad B'])
        ->set('filterSubSource', 'Ad A')
        ->assertSee('Ad A lead')
        ->assertDontSee('Ad B lead')
        ->set('filterSource', 'SMS')
        ->assertSet('filterSubSource', '')
        ->set('filterSource', '')
        ->set('filterSubStatus', 'Call again')
        ->assertSee('Ad A lead')
        ->assertDontSee('Ad B lead')
        ->set('filterSubStatus', '')
        ->set('filterCountryId', $country->id)
        ->assertSee('Ad A lead')
        ->assertDontSee('Ad B lead');
});

it('shows and hides lead list columns from the column visibility panel', function (): void {
    \App\Models\PropertyLead::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'name' => 'Column lead',
        'type' => 'Sales',
        'source' => 'Walk-In',
        'status' => 'New Lead',
        'sub_status' => 'Waiting on HR',
        'email' => 'column@example.com',
    ]);

    $table = \Livewire\Livewire::test(\App\Livewire\Property\PropertyLead\Table::class)
        ->assertSee('column@example.com')
        ->assertViewHas('columns', fn (array $columns) => isset($columns['email']) && ! isset($columns['sub_status']))
        ->assertDontSeeHtml('<span class="small">Waiting on HR</span>');

    \Livewire\Livewire::test(\App\Livewire\Property\PropertyLead\ColumnVisibility::class)
        ->call('toggleColumn', 'email')
        ->call('toggleColumn', 'sub_status')
        ->assertDispatched('PropertyLead-Refresh-Component');

    $table->call('$refresh')
        ->assertDontSee('column@example.com')
        ->assertViewHas('columns', fn (array $columns) => ! isset($columns['email']) && $columns['sub_status'] === 'Sub Status')
        ->assertSeeHtml('<span class="small">Waiting on HR</span>');

    \Livewire\Livewire::test(\App\Livewire\Property\PropertyLead\ColumnVisibility::class)->call('resetToDefaults');

    expect(\App\Livewire\Property\PropertyLead\ColumnVisibility::current())
        ->email->toBeTrue()
        ->sub_status->toBeFalse();
});
