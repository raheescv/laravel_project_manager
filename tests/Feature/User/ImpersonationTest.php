<?php

use App\Models\Branch;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function impersonatorAdmin(): User
{
    $permission = Permission::firstOrCreate(['tenant_id' => 1, 'name' => 'user.impersonate'], ['guard_name' => 'web']);
    $role = Role::firstOrCreate(['tenant_id' => 1, 'name' => 'Impersonation Admin'], ['guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create(['tenant_id' => 1, 'is_active' => 1]);
    $admin->assignRole($role);

    return $admin;
}

test('an admin can return to their own account after impersonating', function (): void {
    $admin = impersonatorAdmin();
    $target = User::factory()->create(['tenant_id' => 1, 'is_active' => 1]);

    $this->actingAs($admin);
    app(ImpersonationService::class)->start($target);

    expect(Auth::id())->toBe($target->id);

    $this->get(route('users::impersonate-leave'))
        ->assertRedirect(route('users::view', $target->id));

    expect(Auth::id())->toBe($admin->id)
        ->and(session()->has('impersonator_id'))->toBeFalse();
});

test('leaving impersonation is refused when not impersonating', function (): void {
    $admin = impersonatorAdmin();

    $this->actingAs($admin)
        ->get(route('users::impersonate-leave'))
        ->assertForbidden();

    expect(Auth::id())->toBe($admin->id);
});

test('impersonation expires after the configured window and restores the admin', function (): void {
    $admin = impersonatorAdmin();
    $target = User::factory()->create(['tenant_id' => 1, 'is_active' => 1]);

    $this->actingAs($admin);
    app(ImpersonationService::class)->start($target);
    expect(Auth::id())->toBe($target->id);

    $this->travel(ImpersonationService::DURATION_MINUTES + 1)->minutes();

    // Any subsequent request should drop the session back to the admin.
    $this->get('/dashboard')->assertRedirect(route('dashboard'));

    expect(Auth::id())->toBe($admin->id)
        ->and(session()->has('impersonator_id'))->toBeFalse();
});

test('the impersonation window is still open before it elapses', function (): void {
    $admin = impersonatorAdmin();
    $target = User::factory()->create(['tenant_id' => 1, 'is_active' => 1]);

    $this->actingAs($admin);
    $service = app(ImpersonationService::class);
    $service->start($target);

    $this->travel(ImpersonationService::DURATION_MINUTES - 1)->minutes();

    expect($service->hasExpired())->toBeFalse()
        ->and($service->secondsRemaining())->toBeGreaterThan(0)
        ->and(Auth::id())->toBe($target->id);
});

test('the original branch is restored when impersonation ends', function (): void {
    $admin = impersonatorAdmin();
    $target = User::factory()->create(['tenant_id' => 1, 'is_active' => 1]);
    $branch = Branch::first() ?? Branch::create(['tenant_id' => 1, 'name' => 'Impersonation Branch', 'code' => 'IMP']);

    $this->actingAs($admin);
    session(['branch_id' => $branch->id]);

    $service = app(ImpersonationService::class);
    $service->start($target);
    $service->stop();

    expect(Auth::id())->toBe($admin->id)
        ->and(session('branch_id'))->toBe($branch->id);
});
