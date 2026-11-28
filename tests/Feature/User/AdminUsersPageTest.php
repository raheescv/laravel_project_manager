<?php

use App\Livewire\User\Table;
use App\Models\User;
use App\Models\UserHasBranch;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * /users is labelled "Admin Users" in both the sidebar link and the page title.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'user.view', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('labels the users list as Admin Users in the sidebar and page title', function (): void {
    $this->get($this->world->url('/users'))
        ->assertSuccessful()
        ->assertSee('<div class="h-ref">Admin Users</div>', false)
        ->assertSee('Admin Users</a>', false)
        ->assertSee('collapse show">Admin</span>', false)
        ->assertDontSee('Users Directory');
});

it('defaults the branch facet to the current branch and filters by assigned branch', function (): void {
    $second = $this->world->addBranch();
    UserHasBranch::create(['user_id' => $this->world->user->id, 'branch_id' => $second->id]);
    $makeUser = function (string $name, array $branchIds): void {
        $user = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'type' => 'user', 'name' => $name]);
        foreach ($branchIds as $branchId) {
            UserHasBranch::create(['user_id' => $user->id, 'branch_id' => $branchId]);
        }
    };
    $makeUser('Main Admin', [$this->world->branch->id]);
    $makeUser('Both Admin', [$this->world->branch->id, $second->id]);
    $makeUser('Second Admin', [$second->id]);

    $component = Livewire::test(Table::class);

    expect($component->get('branch_id'))->toBe((string) $this->world->branch->id)
        ->and($component->viewData('data')->pluck('name')->all())
        ->toContain('Main Admin', 'Both Admin')
        ->not->toContain('Second Admin')
        ->and((int) $component->viewData('branchCounts')[$second->id])->toBe(3);

    $component->call('setBranch', $second->id);
    expect($component->viewData('data')->pluck('name')->all())
        ->toContain('Both Admin', 'Second Admin')
        ->not->toContain('Main Admin');

    $component->call('resetFilters');
    expect($component->get('branch_id'))->toBe('')
        ->and($component->viewData('data')->pluck('name')->all())
        ->toContain('Main Admin', 'Both Admin', 'Second Admin');
});

it('lists and accepts only the branches the signed-in user has access to', function (): void {
    $second = $this->world->addBranch();
    $hidden = $this->world->addBranch('Hidden Branch', 'HB');
    UserHasBranch::where('user_id', $this->world->user->id)->delete();
    foreach ([$this->world->branch->id, $second->id] as $branchId) {
        UserHasBranch::create(['user_id' => $this->world->user->id, 'branch_id' => $branchId]);
    }

    $component = Livewire::test(Table::class);

    expect(array_keys($component->viewData('branches')))
        ->toEqualCanonicalizing([$this->world->branch->id, $second->id])
        ->not->toContain($hidden->id);

    $component->call('setBranch', $hidden->id);
    expect($component->get('branch_id'))->toBe((string) $this->world->branch->id);
});
