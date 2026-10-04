<?php

use App\Livewire\Settings\LeadDropdownOptions;
use App\Models\PropertyLead;
use App\Support\LeadOptions;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Settings → Lead Settings edits the lead source and status dropdowns and
 * their sub lists. Until something is saved the built-in lists apply.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);

    $permission = config('permission.models.permission');
    $this->world->user->givePermissionTo($permission::firstOrCreate(['name' => 'property lead.settings', 'guard_name' => 'web']));
});

function leadOptionsLead(array $attributes = []): PropertyLead
{
    return PropertyLead::create([
        'tenant_id' => test()->world->tenant->id,
        'branch_id' => test()->world->branch->id,
        'name' => 'Lead '.uniqid(),
        'type' => 'Sales',
        'source' => 'Walk-In',
        'status' => 'New Lead',
        ...$attributes,
    ]);
}

it('falls back to the built-in lists until one is saved', function (): void {
    expect(leadSources())->toHaveKey('Walk-In')
        ->and(leadStatuses())->toHaveKey('Closed Deal');
});

it('adds a source and orders statuses by their order no', function (): void {
    Livewire::test(LeadDropdownOptions::class)
        ->set('newValue', 'Billboard')
        ->call('add')
        ->assertHasNoErrors()
        ->call('setList', 'statuses')
        ->set('newValue', 'Hot')
        ->set('newOrder', 0)
        ->call('add');

    expect(leadSources())->toHaveKey('Billboard')
        ->and(array_key_first(leadStatuses()))->toBe('Hot');
});

it('refuses a duplicate regardless of case', function (): void {
    Livewire::test(LeadDropdownOptions::class)
        ->set('newValue', 'walk-in')
        ->call('add')
        ->assertHasErrors('newValue');
});

it('renames a status on the leads that carry it, drifted spellings included', function (): void {
    $lead = leadOptionsLead(['status' => 'follow up ']);

    Livewire::test(LeadDropdownOptions::class)
        ->call('setList', 'statuses')
        ->call('edit', 'Follow Up')
        ->set('editValue', 'Following Up')
        ->call('update')
        ->assertHasNoErrors();

    expect(leadStatuses())->toHaveKey('Following Up')->not->toHaveKey('Follow Up')
        ->and($lead->fresh()->status)->toBe('Following Up');
});

it('manages sub sources that the lead form then offers', function (): void {
    Livewire::test(LeadDropdownOptions::class)
        ->call('toggleSubs', 'Facebook')
        ->set('newSub', 'Ad A')
        ->call('addSub')
        ->set('newSub', 'Ad B')
        ->call('addSub')
        ->call('editSub', 0)
        ->set('editSubValue', 'Ad A (Ramadan)')
        ->call('updateSub')
        ->call('deleteSub', 1);

    expect(leadSubOptions(LeadOptions::SUB_SOURCES, 'Facebook'))->toBe(['Ad A (Ramadan)' => 'Ad A (Ramadan)']);
});

it('removes a value from the list but leaves it on existing leads', function (): void {
    $lead = leadOptionsLead(['source' => 'Snapchat']);

    Livewire::test(LeadDropdownOptions::class)->call('delete', 'Snapchat');

    expect(leadSources())->not->toHaveKey('Snapchat')
        ->and($lead->fresh()->source)->toBe('Snapchat');
});

it('is closed to users without the settings permission', function (): void {
    $this->actingAs(\App\Models\User::factory()->create());

    Livewire::test(LeadDropdownOptions::class)->assertForbidden();
});
