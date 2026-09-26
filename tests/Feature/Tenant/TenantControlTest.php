<?php

use App\Actions\Tenant\ProvisionAction;
use App\Actions\Tenant\ToggleStatusAction;
use App\Livewire\Tenant\Table;
use App\Livewire\Tenant\View;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Configuration;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ImpersonationService;
use App\Services\TenantAnalyticsService;
use App\Services\TenantService;
use App\Services\TenantSwitchService;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Tenant Control: the super admin, signed into their own tenant, looks at,
 * provisions, switches off and switches into OTHER tenants.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $this->other = Tenant::factory()->create(['name' => 'Acme Retail']);
});

/** `total`/`grand_total` are generated columns, so only gross_amount is written. */
function recordSale(PosWorld $world, int $tenantId, int $branchId, float $total, string $date, string $status = 'completed'): void
{
    Sale::withoutGlobalScopes()->insert([
        'tenant_id' => $tenantId,
        'branch_id' => $branchId,
        'account_id' => $world->cashAccountId,
        'created_by' => $world->user->id,
        'invoice_no' => 'T'.uniqid(),
        'date' => $date,
        'gross_amount' => $total,
        'status' => $status,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('opens the tenant view page for super admins only', function (): void {
    $this->actingAs($this->world->user)
        ->get($this->world->url('/tenants/view/'.$this->other->id))
        ->assertOk()
        ->assertSee('Acme Retail');

    $this->world->user->forceFill(['is_super_admin' => false])->save();
    $this->actingAs($this->world->user->fresh())
        ->get($this->world->url('/tenants/view/'.$this->other->id))
        ->assertForbidden();
});

it('lists every tenant with counts from its own rows', function (): void {
    User::factory()->count(2)->create(['tenant_id' => $this->other->id]);

    Livewire::actingAs($this->world->user)->test(Table::class)
        ->assertSee('Acme Retail')
        ->assertViewHas('data', fn ($data) => $data->firstWhere('id', $this->other->id)->users_count === 2)
        ->set('status', 'inactive')
        ->assertDontSee('Acme Retail');
});

it('builds analytics from the target tenant only', function (): void {
    $otherBranch = Branch::create(['tenant_id' => $this->other->id, 'name' => 'Doha', 'code' => 'DH']);
    recordSale($this->world, $this->other->id, $otherBranch->id, 120, now()->toDateString());
    recordSale($this->world, $this->other->id, $otherBranch->id, 80, now()->subMonths(3)->toDateString());
    recordSale($this->world, $this->other->id, $otherBranch->id, 999, now()->toDateString(), 'draft');
    recordSale($this->world, $this->world->tenant->id, $this->world->branch->id, 5000, now()->toDateString());

    $summary = app(TenantAnalyticsService::class)->summary($this->other);

    expect($summary['sales']['count'])->toBe(2)
        ->and($summary['sales']['total'])->toBe(200.0)
        ->and($summary['sales']['this_month'])->toBe(120.0)
        ->and($summary['branches'])->toBe(1)
        ->and($summary['trend'])->toHaveCount(12)
        ->and(end($summary['trend'])['total'])->toBe(120.0)
        ->and($summary['top_branches'][0]['name'])->toBe('Doha');
});

it('provisions a tenant idempotently without touching the current tenant', function (): void {
    $ownAccounts = Account::withTenant($this->world->tenant->id)->count();

    $first = (new ProvisionAction())->execute($this->other->id, ['name' => 'Acme Admin', 'email' => 'admin@acme.test', 'password' => 'secret-pass'], 'POS Module');

    expect($first['success'])->toBeTrue($first['message'])
        ->and(collect($first['steps'])->where('status', 'created')->count())->toBe(count($first['steps']));

    $cashAndCard = Account::withTenant($this->other->id)->whereIn('slug', ['cash', 'card'])->pluck('id')->all();
    expect(Account::withTenant($this->other->id)->where('slug', 'cash')->exists())->toBeTrue()
        ->and(Branch::withTenant($this->other->id)->count())->toBe(1)
        ->and(Permission::where('tenant_id', $this->other->id)->count())->toBeGreaterThan(0)
        ->and(json_decode(Configuration::withTenant($this->other->id)->where('key', 'payment_methods')->value('value'), true))->toEqualCanonicalizing($cashAndCard)
        ->and(Configuration::withTenant($this->other->id)->where('key', 'active_module')->value('value'))->toBe('POS Module')
        ->and(Account::withTenant($this->world->tenant->id)->count())->toBe($ownAccounts);

    $admin = User::withTenant($this->other->id)->where('email', 'admin@acme.test')->first();
    expect($admin->is_admin)->toBeTruthy()
        ->and($admin->roles->pluck('tenant_id')->all())->toBe([$this->other->id]);

    $second = (new ProvisionAction())->execute($this->other->id, ['email' => 'admin@acme.test']);
    expect($second['success'])->toBeTrue()
        ->and(collect($second['steps'])->pluck('status')->unique()->all())->toBe(['present'])
        ->and(app(TenantService::class)->getCurrentTenantId())->toBe($this->world->tenant->id);
});

it('seeds the default users into the provisioned tenant without super admin', function (): void {
    $response = (new ProvisionAction())->execute($this->other->id);
    expect($response['success'])->toBeTrue($response['message']);

    $users = User::withTenant($this->other->id)->with('roles')->get()->keyBy('email');
    $branchId = Branch::withTenant($this->other->id)->value('id');

    expect($users->keys()->all())->toEqualCanonicalizing(['system@astra.com', 'admin@astra.com', 'rahees@astra.com', 'employee@astra.com'])
        ->and($users->every(fn (User $user) => ! $user->is_super_admin))->toBeTrue()
        ->and($users->every(fn (User $user) => $user->roles->pluck('tenant_id')->all() === [$this->other->id]))->toBeTrue()
        ->and($users['admin@astra.com']->default_branch_id)->toBe($branchId);

    (new ProvisionAction())->execute($this->other->id);
    expect(User::withTenant($this->other->id)->count())->toBe(4);
});

it('grants and removes a tenant user\'s roles from the Users tab', function (): void {
    (new ProvisionAction())->execute($this->other->id);
    $employee = User::withTenant($this->other->id)->where('email', 'employee@astra.com')->first();
    $role = Spatie\Permission\Models\Role::where('tenant_id', $this->other->id)->where('name', 'Admin')->first();
    $foreignRole = Spatie\Permission\Models\Role::firstOrCreate(['tenant_id' => $this->world->tenant->id, 'name' => 'Admin', 'guard_name' => 'web']);

    $component = Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->other->id])
        ->call('selectTab', 'users')
        ->call('editAccess', $employee->id)
        ->assertSee('Tap to grant or remove')
        ->call('toggleRole', $employee->id, $role->id)
        ->assertDispatched('success');
    expect($employee->fresh()->hasRole($role))->toBeFalse();

    $component->call('toggleRole', $employee->id, $role->id)
        ->call('toggleAdmin', $employee->id);
    expect($employee->fresh()->hasRole($role))->toBeTrue()
        ->and($employee->fresh()->is_admin)->toBeTruthy();

    expect(fn () => $component->call('toggleRole', $employee->id, $foreignRole->id))->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect($employee->fresh()->roles->pluck('id')->all())->toBe([$role->id]);
});

it('never deactivates or deletes the tenant you are signed into', function (): void {
    $this->actingAs($this->world->user);

    expect((new ToggleStatusAction())->execute($this->world->tenant->id, false)['success'])->toBeFalse()
        ->and((new ToggleStatusAction())->execute($this->other->id, false)['success'])->toBeTrue()
        ->and($this->other->fresh()->is_active)->toBeFalse();

    Livewire::actingAs($this->world->user)->test(Table::class)
        ->set('selected', [$this->world->tenant->id])
        ->call('delete')
        ->assertDispatched('error');
    expect($this->world->tenant->fresh()->deleted_at)->toBeNull();
});

it('stops serving a deactivated tenant straight away', function (): void {
    expect(app(TenantService::class)->findTenantBySubdomain($this->other->subdomain)?->id)->toBe($this->other->id);

    (new ToggleStatusAction())->execute($this->other->id, false);

    expect(app(TenantService::class)->findTenantBySubdomain($this->other->subdomain))->toBeNull();
});

it('switches into another tenant with a single-use token and returns home on leave', function (): void {
    $target = User::factory()->create(['tenant_id' => $this->other->id, 'is_admin' => 1, 'is_active' => 1]);
    $switch = app(TenantSwitchService::class);

    $url = $switch->entryUrl($this->other, $this->world->user, 'http://home.localhost/tenants/view/'.$this->other->id);
    expect($url)->toContain($this->other->subdomain.'.')->toContain('/tenants/enter/');

    $enterUrl = 'http://'.$this->other->subdomain.'.localhost'.parse_url($url, PHP_URL_PATH);

    $this->get($enterUrl)->assertRedirect(route('dashboard'));
    expect(Auth::id())->toBe($target->id)
        ->and(app(ImpersonationService::class)->returnUrl())->toContain('/tenants/view/');

    $this->get('http://'.$this->other->subdomain.'.localhost/users/impersonate/leave')
        ->assertRedirect('http://home.localhost/tenants/view/'.$this->other->id);
    expect(Auth::check())->toBeFalse();

    // The token was consumed by the first visit.
    $this->get($enterUrl)->assertForbidden();
});

it('refuses a switch token on a different tenant host', function (): void {
    User::factory()->create(['tenant_id' => $this->other->id, 'is_admin' => 1, 'is_active' => 1]);

    $url = app(TenantSwitchService::class)->entryUrl($this->other, $this->world->user, 'http://home.localhost');

    $this->get($this->world->url(parse_url($url, PHP_URL_PATH)))->assertForbidden();
    expect(Auth::check())->toBeFalse();
});

it('will not switch into the current, an inactive or an empty tenant', function (): void {
    $switch = app(TenantSwitchService::class);

    expect(fn () => $switch->entryUrl($this->world->tenant, $this->world->user, '/'))->toThrow(Exception::class, 'already signed into')
        ->and(fn () => $switch->entryUrl($this->other, $this->world->user, '/'))->toThrow(Exception::class, 'no active user');

    $this->other->update(['is_active' => false]);
    expect(fn () => $switch->entryUrl($this->other->fresh(), $this->world->user, '/'))->toThrow(Exception::class, 'Activate the tenant');
});

it('provisions from the Seeding tab', function (): void {
    Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->other->id])
        ->call('selectTab', 'seeding')
        ->set('provision.system', 'POS Module')
        ->call('runProvision')
        ->assertDispatched('success')
        ->assertSet('provisionSteps', fn (array $steps) => count($steps) === 8)
        ->assertSee('added')
        ->assertViewHas('summary', fn (array $summary) => $summary['branches'] === 1);
});

it('renders every tab of the view page', function (): void {
    User::factory()->create(['tenant_id' => $this->other->id, 'name' => 'Mariam Staff']);
    Branch::create(['tenant_id' => $this->other->id, 'name' => 'Lusail', 'code' => 'LS']);

    Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->other->id])
        ->assertSee('Profile')
        ->call('selectTab', 'users')->assertSee('Mariam Staff')
        ->call('selectTab', 'branches')->assertSee('Lusail')
        ->call('selectTab', 'analytics')->assertSee('Sales · last 12 months')
        ->call('refreshAnalytics')->assertDispatched('success')
        ->call('selectTab', 'seeding')->assertSee('Run provisioning');
});

it('restores a deleted tenant from its view page', function (): void {
    $this->other->delete();

    Livewire::actingAs($this->world->user)->test(View::class, ['tenantId' => $this->other->id])
        ->assertSee('Restore')
        ->call('restore')
        ->assertDispatched('success');

    expect($this->other->fresh()->deleted_at)->toBeNull();
});

it('builds the workspace address from the subdomain, never a copied domain', function (): void {
    config(['app.url' => 'https://project_manager.test']);
    $tenant = Tenant::factory()->create(['subdomain' => 'solan', 'domain' => 'project_manager.test']);

    expect($tenant->url('tenants/enter/abc'))->toBe('https://solan.test/tenants/enter/abc');
});

it('creates and edits tenants from the modal', function (): void {
    Livewire::actingAs($this->world->user)->test(\App\Livewire\Tenant\Page::class)
        ->assertSee('New tenant')
        ->set('tenants.name', 'Nova Mart')
        ->set('tenants.code', 'NOVA')
        ->set('tenants.subdomain', 'nova')
        ->assertSee('nova'.Tenant::subdomainSuffix())
        ->set('tenants.domain', '')
        ->set('tenants.is_active', '0')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('success');

    $tenant = Tenant::where('code', 'NOVA')->first();
    expect($tenant->is_active)->toBeFalse();

    Livewire::actingAs($this->world->user)->test(\App\Livewire\Tenant\Page::class)
        ->call('edit', $tenant->id)
        ->assertSee('Edit tenant')
        ->set('tenants.domain', parse_url(config('app.url'), PHP_URL_HOST))
        ->call('save')
        ->assertHasErrors(['tenants.domain' => 'not_in']);
});
