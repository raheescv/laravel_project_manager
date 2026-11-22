<?php

use App\Livewire\Service\Table;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The service list's floating bulk bar: shown only with a selection, and able
 * to flip the "is selling" and "is favorite" flags on every selected service.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    foreach (['service.edit', 'service.delete'] as $permission) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->service = $this->world->product;
    $this->service->update(['type' => 'service', 'is_selling' => true, 'is_favorite' => true]);
});

it('shows the floating bulk bar only when services are selected', function (): void {
    Livewire::test(Table::class)
        ->assertDontSeeHtml('class="service-bulk-bar"')
        ->assertDontSeeHtml('title="Delete Selected"')
        ->set('selected', [$this->service->id])
        ->assertSeeHtml('class="service-bulk-bar"')
        ->assertSee('Mark as not selling')
        ->assertSee('Remove from favorites');
});

it('marks the selected services as not selling and back', function (): void {
    Livewire::test(Table::class)
        ->set('selected', [$this->service->id])
        ->call('updateSelling', false)
        ->assertSet('selected', [])
        ->assertDispatched('success');

    expect((bool) $this->service->fresh()->is_selling)->toBeFalse();

    Livewire::test(Table::class)
        ->set('selected', [$this->service->id])
        ->call('updateSelling', true);

    expect((bool) $this->service->fresh()->is_selling)->toBeTrue();
});

it('marks the selected services as not favorite and back', function (): void {
    Livewire::test(Table::class)
        ->set('selected', [$this->service->id])
        ->call('updateFavorite', false)
        ->assertSet('selected', [])
        ->assertDispatched('success');

    expect((bool) $this->service->fresh()->is_favorite)->toBeFalse();

    Livewire::test(Table::class)
        ->set('selected', [$this->service->id])
        ->call('updateFavorite', true);

    expect((bool) $this->service->fresh()->is_favorite)->toBeTrue();
});

it('refuses a bulk update with nothing selected', function (): void {
    Livewire::test(Table::class)
        ->call('updateFavorite', false)
        ->assertDispatched('error');

    expect((bool) $this->service->fresh()->is_favorite)->toBeTrue();
});

it('forbids a bulk update without service edit permission', function (): void {
    $this->world->user->revokePermissionTo('service.edit');

    Livewire::test(Table::class)
        ->set('selected', [$this->service->id])
        ->call('updateSelling', false)
        ->assertForbidden();
});
