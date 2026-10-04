<?php

use App\Livewire\Settings\ModuleConfiguration;
use App\Models\Configuration;
use Livewire\Livewire;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $this->actingAs($this->world->user->fresh());
});

it('shows every system with its modules and marks the live one', function (): void {
    Configuration::updateOrCreate(['tenant_id' => $this->world->tenant->id, 'key' => 'active_module'], ['value' => 'POS Module']);

    Livewire::test(ModuleConfiguration::class)
        ->assertSet('active_module', 'POS Module')
        ->assertSet('saved_module', 'POS Module')
        ->assertSee('Live: POS Module')
        ->assertSee('School Module')
        ->assertSee('System Administration')
        ->assertDontSee('Unsaved:');
});

it('previews the modules a switch adds and removes before saving', function (): void {
    Configuration::updateOrCreate(['tenant_id' => $this->world->tenant->id, 'key' => 'active_module'], ['value' => 'POS Module']);

    Livewire::test(ModuleConfiguration::class)
        ->set('active_module', 'School Module')
        ->assertSee('Unsaved: School Module')
        ->assertViewHas('addedModules', fn (array $added): bool => in_array('school', $added, true))
        ->assertViewHas('removedModules', [])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved_module', 'School Module')
        ->assertSee('Live: School Module');

    expect(Configuration::where('key', 'active_module')->value('value'))->toBe('School Module');
});

it('rejects an unknown system', function (): void {
    Livewire::test(ModuleConfiguration::class)
        ->set('active_module', 'Space Module')
        ->call('save')
        ->assertHasErrors(['active_module']);
});

it('is only for super admins', function (): void {
    $this->world->user->forceFill(['is_super_admin' => false])->save();
    $this->actingAs($this->world->user->fresh());

    Livewire::test(ModuleConfiguration::class)->assertForbidden();
});
