<?php

use App\Livewire\Settings\Role\Permissions as RolePermissions;
use App\Models\Configuration;
use App\Models\PropertyLead;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\Dashboard\WidgetRegistry;
use App\Support\ModuleAccess;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The Vue home dashboard: the catalogue a user is sent, each widget's JSON
 * endpoint, and the per-user layout behind the `dashboard.customize` editor.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id, 'branch_name' => $this->world->branch->name]);
    $this->actingAs($this->world->user);

    $this->grant = function (string ...$names): void {
        $permission = config('permission.models.permission');
        foreach ($names as $name) {
            $this->world->user->givePermissionTo($permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
    };
    $this->grantAllWidgets = function (): void {
        $names = collect(app(WidgetRegistry::class)->all())->flatMap(fn ($definition) => $definition->permissions)->unique()->all();
        ($this->grant)(...$names);
    };
    $this->widget = fn (string $key, array $query = []) => $this->getJson($this->world->url("/dashboard/widgets/{$key}").($query ? '?'.http_build_query($query) : ''));
    $this->saveLayout = fn (array $layout) => $this->putJson($this->world->url('/dashboard/layout'), ['layout' => $layout]);
    $this->lead = fn (array $attributes = []): PropertyLead => PropertyLead::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'name' => 'Lead '.uniqid(),
        'type' => 'Sales',
        'source' => 'Walk-In',
        'status' => 'New Lead',
        ...$attributes,
    ]);
});

it('sends only the widgets the user may see, and no editor without dashboard.customize', function (): void {
    ($this->grant)('sale.dashboard weekly summary', 'appointment.dashboard');

    $props = $this->get($this->world->url('/'))->assertOk()->assertSee('id="dashboard-app"', false)->viewData('dashboard');

    expect(collect($props['catalogue'])->pluck('key')->all())->toBe(['business-kpis', 'appointment-chart', 'upcoming-appointments'])
        ->and(collect($props['layout'])->pluck('key')->filter()->values()->all())->toBe(['business-kpis', 'appointment-chart', 'upcoming-appointments'])
        ->and(collect($props['layout'])->where('type', 'divider')->pluck('title')->values()->all())->toBe(['Business overview', 'Appointments'])
        ->and($props['canCustomize'])->toBeFalse();

    ($this->grant)('dashboard.customize');
    expect($this->get($this->world->url('/'))->viewData('dashboard')['canCustomize'])->toBeTrue();
});

it('loads every widget without errors', function (string $key): void {
    ($this->grantAllWidgets)();
    $definition = app(WidgetRegistry::class)->find($key);

    // A widget of an opt-in module (Agreements) exists only for tenants whose
    // system runs that module, so switch to the first such system.
    if (! $definition->isVisibleTo($this->world->user)) {
        $system = collect(config('modules.systems'))->keys()->first(function (string $system) use ($definition): bool {
            [$groups, $exact] = ModuleAccess::systemPermissions($system);

            return collect($definition->permissions)->every(fn (string $permission): bool => in_array($permission, $exact, true) || in_array(Str::before($permission, '.'), $groups, true));
        });
        Configuration::create(['tenant_id' => $this->world->tenant->id, 'key' => 'active_module', 'value' => $system]);
    }

    foreach ($definition->options ?: [null => null] as $option => $label) {
        $response = ($this->widget)($key, array_filter(['option' => $option]))->assertOk();
        expect($response->json('blocks'))->toBeArray()->not->toBeEmpty();
    }
})->with(fn () => array_keys(app(WidgetRegistry::class)->all()));

it('blocks a widget without its permission and 404s an unknown one', function (): void {
    ($this->grant)('property lead.view');

    ($this->widget)('lead-status')->assertForbidden();
    ($this->widget)('business-kpis')->assertForbidden();
    ($this->widget)('no-such-widget')->assertNotFound();
});

it('counts leads by stage for the current tenant and branch only', function (): void {
    ($this->grant)('property lead.view', 'property lead.view all', 'property lead.dashboard status count');
    ($this->lead)(['status' => 'New Lead']);
    ($this->lead)(['status' => 'Closed Deal']);
    ($this->lead)(['status' => 'Closed Deal']);
    ($this->lead)(['status' => 'Call Back', 'branch_id' => $this->world->addBranch()->id]);

    $kpis = collect(($this->widget)('lead-status')->assertOk()->json('blocks.0.items'))->pluck('value', 'label');

    expect($kpis['Total leads'])->toBe(3)
        ->and($kpis['New this week'])->toBe(1)
        ->and($kpis['Closed deals'])->toBe(2)
        ->and($kpis['Call backs'])->toBe(0);

    $other = Tenant::factory()->create();
    PropertyLead::withoutGlobalScopes()->create(['tenant_id' => $other->id, 'branch_id' => $this->world->branch->id, 'name' => 'Elsewhere', 'type' => 'Sales', 'status' => 'Closed Deal']);
    expect(collect(($this->widget)('lead-status')->json('blocks.0.items'))->pluck('value', 'label')['Closed deals'])->toBe(2);
});

it('moves the package calendar between views and dates', function (): void {
    ($this->grant)('package.dashboard package calendar');

    $month = ($this->widget)('package-calendar', ['option' => 'month', 'date' => '2026-02-10'])->assertOk()->json('blocks.0');
    expect($month)->view->toBe('month')->title->toBe('February 2026')->prev->toBe('2026-01-01')->next->toBe('2026-03-01')
        ->and($month['days'])->toHaveCount(28)->and($month['blanks'])->toBe(0);

    expect(($this->widget)('package-calendar', ['option' => 'year', 'date' => '2026-02-10'])->json('blocks.0.months'))->toHaveCount(12);
    ($this->widget)('package-calendar', ['date' => '10/02/2026'])->assertJsonValidationErrors(['date']);
});

it('saves a layout for the signed-in user only', function (): void {
    ($this->grant)('dashboard.customize', 'sale.dashboard weekly summary', 'sale.dashboard bar chart');
    $layout = [
        ['type' => 'divider', 'id' => 'section-top', 'title' => 'Today'],
        ['key' => 'sale-overview', 'span' => 12, 'collapsed' => true],
        ['key' => 'business-kpis', 'span' => 6, 'collapsed' => false],
    ];

    ($this->saveLayout)($layout)->assertOk()->assertJsonPath('layout', $layout);

    expect(UserPreference::getValue(WidgetRegistry::PREFERENCE_KEY))->toEqual($layout)
        ->and($this->get($this->world->url('/'))->viewData('dashboard')['layout'])->toBe($layout);

    $colleague = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'default_branch_id' => $this->world->branch->id]);
    expect(UserPreference::getValue(WidgetRegistry::PREFERENCE_KEY, null, $colleague->id))->toBeNull();
});

it('rejects layouts that are not allowed', function (array $layout, string $error): void {
    ($this->grant)('dashboard.customize', 'sale.dashboard weekly summary', 'sale.dashboard bar chart', 'package.dashboard package calendar');

    ($this->saveLayout)($layout)->assertUnprocessable()->assertJsonValidationErrors([$error]);
})->with([
    'unknown widget' => [[['key' => 'no-such-widget', 'span' => 4]], 'layout.0.key'],
    'widget without permission' => [[['key' => 'lead-status', 'span' => 8]], 'layout.0.key'],
    'odd width' => [[['key' => 'business-kpis', 'span' => 5]], 'layout.0.span'],
    'narrower than allowed' => [[['key' => 'package-calendar', 'span' => 6]], 'layout.0.span'],
    'duplicate widget' => [[['key' => 'business-kpis', 'span' => 12], ['key' => 'business-kpis', 'span' => 6]], 'layout.0.key'],
    'divider without a title' => [[['type' => 'divider', 'id' => 'x']], 'layout.0.title'],
]);

it('refuses to save without dashboard.customize', function (): void {
    ($this->grant)('sale.dashboard weekly summary');

    ($this->saveLayout)([['key' => 'business-kpis', 'span' => 12]])->assertForbidden();
});

it('keeps hidden widgets hidden, drops revoked ones and appends new ones', function (): void {
    ($this->grant)('dashboard.customize', 'sale.dashboard weekly summary', 'sale.dashboard bar chart', 'sale.dashboard top items');
    ($this->saveLayout)([['key' => 'sale-overview', 'span' => 8], ['key' => 'business-kpis', 'span' => 12]])->assertOk();

    $keys = fn (): array => collect(app(WidgetRegistry::class)->layoutFor($this->world->user->fresh()))->pluck('key')->filter()->values()->all();
    expect($keys())->toBe(['sale-overview', 'business-kpis']);

    $this->world->user->revokePermissionTo('sale.dashboard bar chart');
    ($this->grant)('appointment.dashboard');

    expect($keys())->toBe(['business-kpis', 'appointment-chart', 'upcoming-appointments']);
});

it('grants dashboard.customize to every role in every tenant and to users without a role', function (): void {
    $sales = Role::create(['name' => 'Sales', 'guard_name' => 'web', 'tenant_id' => $this->world->tenant->id]);
    $other = Tenant::factory()->create();
    $otherAdmin = Role::create(['name' => 'Admin', 'guard_name' => 'web', 'tenant_id' => $other->id]);
    $cashier = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'default_branch_id' => $this->world->branch->id]);
    $cashier->assignRole($sales);

    (require database_path('migrations/2026_10_12_232732_grant_dashboard_customize_to_everyone.php'))->up();

    expect($sales->fresh()->hasPermissionTo('dashboard.customize'))->toBeTrue()
        ->and($otherAdmin->fresh()->hasPermissionTo('dashboard.customize'))->toBeTrue()
        ->and($cashier->fresh()->can('dashboard.customize'))->toBeTrue()
        ->and($this->world->user->fresh()->can('dashboard.customize'))->toBeTrue();
});

it('lists dashboard.customize on the role permission screen for every system', function (?string $system): void {
    Permission::firstOrCreate(['name' => 'dashboard.customize', 'guard_name' => 'web']);
    if ($system) {
        Configuration::updateOrCreate(['key' => 'active_module'], ['value' => $system]);
    }
    $role = Role::create(['name' => 'Cashier', 'guard_name' => 'web', 'tenant_id' => $this->world->tenant->id]);

    expect(Livewire::test(RolePermissions::class, ['role_id' => $role->id])->get('permissions')['dashboard'] ?? [])->toContain('customize');
})->with(fn (): array => [null, ...array_keys((require __DIR__.'/../../../config/modules.php')['systems'])]);

it('charts only the top 15 employees by assigned leads', function (): void {
    ($this->grant)('property lead.view', 'property lead.view all', 'property lead.dashboard employee bar chart');
    foreach (range(1, 17) as $n) {
        $employee = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'default_branch_id' => $this->world->branch->id, 'name' => "Agent {$n}"]);
        foreach (range(1, $n) as $ignored) {
            ($this->lead)(['assigned_to' => $employee->id]);
        }
    }

    $chart = ($this->widget)('lead-employees')->assertOk()->json('blocks.0');

    expect($chart['labels'])->toHaveCount(15)
        ->and($chart['labels'][0])->toBe('Agent 17')
        ->and($chart['series'][0]['values'][0])->toEqual(17)
        ->and($chart['labels'])->not->toContain('Agent 1', 'Agent 2');
});

it('remembers which widgets are pinned to the start of their row', function (): void {
    ($this->grant)('dashboard.customize', 'sale.dashboard weekly summary', 'sale.dashboard bar chart');
    $layout = [
        ['key' => 'sale-overview', 'span' => 6, 'collapsed' => false],
        ['key' => 'business-kpis', 'span' => 8, 'collapsed' => false, 'row' => true],
    ];

    ($this->saveLayout)($layout)->assertOk()->assertJsonPath('layout', $layout);
    ($this->saveLayout)([['key' => 'business-kpis', 'span' => 8, 'row' => 'yes']])->assertJsonValidationErrors(['layout.0.row']);

    expect($this->get($this->world->url('/'))->viewData('dashboard')['layout'])->toBe($layout);
});

it('ignores widget permissions that belong to a system the tenant does not run', function (): void {
    Configuration::updateOrCreate(['tenant_id' => $this->world->tenant->id, 'key' => 'active_module'], ['value' => 'Property Management Module']);
    ($this->grant)('sale.dashboard weekly summary', 'appointment.dashboard', 'package.dashboard package calendar', 'property.dashboard overview', 'property dashboard.maintenance overview');

    $keys = collect($this->get($this->world->url('/'))->assertOk()->viewData('dashboard')['catalogue'])->pluck('key');

    expect($keys)->toContain('property-occupancy', 'maintenance-kpis')
        ->and($keys)->not->toContain('business-kpis', 'appointment-chart', 'package-calendar');
    ($this->widget)('business-kpis')->assertForbidden();
    ($this->widget)('property-occupancy')->assertOk();

    Configuration::updateOrCreate(['tenant_id' => $this->world->tenant->id, 'key' => 'active_module'], ['value' => 'POS Module']);
    expect(collect($this->get($this->world->url('/'))->viewData('dashboard')['catalogue'])->pluck('key'))->toContain('business-kpis')->not->toContain('property-occupancy');
});

it('lays out the lead section like before: tiles and units beside a tall activity feed', function (): void {
    ($this->grant)('property lead.view', 'property lead.dashboard status count', 'property lead.dashboard recent activities', 'property lead.dashboard source pie chart', 'property lead.dashboard employee bar chart', 'dashboard.customize');

    $layout = collect($this->get($this->world->url('/'))->viewData('dashboard')['layout'])->whereNotNull('key')->values();

    expect($layout->pluck('key')->all())->toBe(['lead-status', 'lead-activity', 'lead-units', 'lead-sources', 'lead-employees'])
        ->and($layout->pluck('span')->all())->toBe([8, 4, 8, 6, 6])
        ->and($layout->firstWhere('key', 'lead-activity'))->toMatchArray(['tall' => true]);

    ($this->saveLayout)([['key' => 'lead-status', 'span' => 8, 'tall' => true], ['key' => 'lead-activity', 'span' => 4]])->assertOk()
        ->assertJsonPath('layout.0.tall', true)->assertJsonMissingPath('layout.1.tall');
    ($this->saveLayout)([['key' => 'lead-status', 'span' => 8, 'tall' => 'yes']])->assertJsonValidationErrors(['layout.0.tall']);
});

it('opens the collections widget on Sale, labelled Sale not Lease', function (): void {
    ($this->grant)('property dashboard.financial overview');

    $widget = collect($this->get($this->world->url('/'))->viewData('dashboard')['catalogue'])->firstWhere('key', 'property-financial');

    expect($widget['defaultOption'])->toBe('lease')
        ->and($widget['options'])->toBe([['value' => 'lease', 'label' => 'Sale'], ['value' => 'rental', 'label' => 'Rental']]);
});

it('remembers the switcher option a user last picked as that widget default, per user', function (): void {
    ($this->grant)('property dashboard.financial overview');
    $catalogueOption = fn (): ?string => collect($this->get($this->world->url('/'))->viewData('dashboard')['catalogue'])->firstWhere('key', 'property-financial')['defaultOption'];

    $this->putJson($this->world->url('/dashboard/widgets/property-financial/option'), ['option' => 'rental'])->assertOk()->assertJsonPath('option', 'rental');
    expect($catalogueOption())->toBe('rental');

    $this->putJson($this->world->url('/dashboard/widgets/property-financial/option'), ['option' => 'lease'])->assertOk();
    expect($catalogueOption())->toBe('lease');

    $colleague = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'default_branch_id' => $this->world->branch->id]);
    expect(UserPreference::getValue(WidgetRegistry::OPTIONS_KEY, null, $colleague->id))->toBeNull();
});

it('refuses to remember an unknown option, a hidden widget or an unknown one', function (): void {
    ($this->grant)('property dashboard.financial overview');

    $this->putJson($this->world->url('/dashboard/widgets/property-financial/option'), ['option' => 'weekly'])->assertJsonValidationErrors(['option']);
    $this->putJson($this->world->url('/dashboard/widgets/lead-status/option'), ['option' => 'lease'])->assertForbidden();
    $this->putJson($this->world->url('/dashboard/widgets/no-such-widget/option'), ['option' => 'lease'])->assertNotFound();
});

it('shows upcoming and expired agreements as two tabs of one rent out card', function (): void {
    ($this->grant)('property dashboard.rent out expiry overview');

    $widget = collect($this->get($this->world->url('/'))->viewData('dashboard')['catalogue'])->firstWhere('key', 'rent-agreements');

    expect($widget['defaultOption'])->toBe('upcoming')
        ->and($widget['span'])->toBe(12)
        ->and($widget['options'])->toBe([['value' => 'upcoming', 'label' => 'Upcoming'], ['value' => 'expired', 'label' => 'Expired']])
        ->and(($this->widget)('rent-agreements', ['option' => 'upcoming'])->assertOk()->json('blocks.0.empty'))->toBe('Nothing ends in the next 90 days.')
        ->and(($this->widget)('rent-agreements', ['option' => 'expired'])->assertOk()->json('blocks.0.empty'))->toBe('No expired agreements.');
});

it('moves a saved layout with the old upcoming and expired cards onto the merged card', function (): void {
    ($this->grant)('dashboard.customize', 'property dashboard.rent out expiry overview', 'sale.dashboard weekly summary');
    UserPreference::setValue(WidgetRegistry::PREFERENCE_KEY, [
        ['key' => 'rent-upcoming', 'span' => 6, 'collapsed' => false],
        ['key' => 'rent-expired', 'span' => 6, 'collapsed' => false],
        ['key' => 'business-kpis', 'span' => 12, 'collapsed' => false],
    ], $this->world->user->id);
    UserPreference::setValue(WidgetRegistry::PREFERENCE_KEY.'.seen', ['rent-kpis', 'rent-upcoming', 'rent-expired', 'business-kpis'], $this->world->user->id);

    $layout = app(WidgetRegistry::class)->layoutFor($this->world->user->fresh());

    expect(collect($layout)->pluck('key')->all())->toBe(['rent-agreements', 'business-kpis'])
        ->and($layout[0]['span'])->toBe(12);
});
