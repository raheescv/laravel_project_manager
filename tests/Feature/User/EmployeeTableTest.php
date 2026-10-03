<?php

use App\Livewire\User\Employee\Table;
use App\Models\User;
use App\Models\UserHasBranch;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * /users/employee runs on the Users list's "Facet Rail": roles, designations,
 * branches and status are rail facets whose counts exclude their own dimension.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    foreach (['employee.view', 'employee.edit', 'employee.delete', 'employee.export', 'employee.create'] as $name) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->second = $this->world->addBranch();
});

function rosterEmployee(PosWorld $world, string $name, array $branchIds, bool $active = true): User
{
    $employee = User::factory()->create([
        'tenant_id' => $world->tenant->id,
        'type' => 'employee',
        'name' => $name,
        'is_active' => $active,
        'default_branch_id' => $branchIds[0] ?? null,
    ]);
    foreach ($branchIds as $branchId) {
        UserHasBranch::create(['user_id' => $employee->id, 'branch_id' => $branchId]);
    }

    return $employee;
}

it('renders the facet rail page', function (): void {
    rosterEmployee($this->world, 'Alpha Worker', [$this->world->branch->id]);

    $this->get($this->world->url('/users/employee'))
        ->assertSuccessful()
        ->assertSee('Employee Directory')
        ->assertSee('Alpha Worker');
});

it('filters by branch facet and counts each branch excluding its own filter', function (): void {
    rosterEmployee($this->world, 'Main Only', [$this->world->branch->id]);
    rosterEmployee($this->world, 'Both Branches', [$this->world->branch->id, $this->second->id]);
    rosterEmployee($this->world, 'Second Only', [$this->second->id], active: false);

    $component = Livewire::test(Table::class)->call('setBranch', $this->second->id);

    expect($component->viewData('data')->pluck('name')->all())
        ->toContain('Both Branches', 'Second Only')
        ->not->toContain('Main Only');

    $branchCounts = $component->viewData('branchCounts');
    expect((int) $branchCounts[$this->second->id])->toBe(2)
        ->and((int) $branchCounts[$this->world->branch->id])->toBeGreaterThanOrEqual(2)
        ->and($component->viewData('statusCounts')['inactive'])->toBe(1);

    $component->call('setStatus', '1');
    expect($component->viewData('data')->pluck('name')->all())->toBe(['Both Branches']);

    $component->call('resetFilters');
    expect($component->get('branch_id'))->toBe('')->and($component->get('is_active'))->toBe('');
});

it('maps sort presets and remembers the view mode', function (): void {
    rosterEmployee($this->world, 'Zed', [$this->world->branch->id]);
    rosterEmployee($this->world, 'Abe', [$this->world->branch->id]);

    Livewire::test(Table::class)
        ->set('filter', 'alphabetically')
        ->assertSet('sortField', 'users.name')
        ->assertSet('sortDirection', 'asc')
        ->call('setView', 'grid')
        ->assertSet('view', 'grid');

    expect(session('employees.table.view'))->toBe('grid');
    Livewire::test(Table::class)->assertSet('view', 'grid');
});
