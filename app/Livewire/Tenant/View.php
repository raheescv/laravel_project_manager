<?php

namespace App\Livewire\Tenant;

use App\Actions\Tenant\ProvisionAction;
use App\Livewire\Tenant\Concerns\ControlsTenants;
use App\Models\Branch;
use App\Models\Configuration;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserHasBranch;
use App\Services\TenantAnalyticsService;
use App\Services\TenantServerService;
use App\Services\TenantService;
use App\Services\TenantSwitchService;
use Livewire\Component;

class View extends Component
{
    use ControlsTenants;

    public int $tenantId;

    public string $selected_tab = 'overview';

    /** Tabs opened at least once — users and branches only query once opened. */
    public array $loaded_tabs = ['overview' => true];

    /** @var array{name: string, email: string, password: string, system: string} */
    public array $provision = ['name' => 'Admin', 'email' => '', 'password' => '', 'system' => ''];

    /** @var list<array{key: string, label: string, created: int, status: string}> */
    public array $provisionSteps = [];

    public string $customDomain = '';

    protected $listeners = [
        'Tenant-Refresh-Component' => '$refresh',
    ];

    public function mount(int $tenantId): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403, 'Unauthorized access. Only super admin users can access this page.');

        $this->tenantId = $tenantId;
        $this->provision['system'] = (string) Configuration::withTenant($tenantId)->where('key', 'active_module')->value('value');
    }

    public function selectTab(string $tab): void
    {
        $this->selected_tab = $tab;
        $this->loaded_tabs[$tab] = true;
    }

    public function restore(): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);

        Tenant::onlyTrashed()->findOrFail($this->tenantId)->restore();
        $this->dispatch('success', ['message' => 'Tenant restored']);
    }

    /**
     * Queue the custom domain for the next root-run tenant:server-sync.
     */
    public function requestDomainSync(): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);

        $tenant = Tenant::findOrFail($this->tenantId);
        if (! $tenant->hasCustomDomain()) {
            $this->dispatch('error', ['message' => 'Set a custom domain on this tenant first.']);

            return;
        }
        Tenant::whereKey($tenant->id)->update(['domain_status' => Tenant::DOMAIN_PENDING, 'domain_error' => null]);
        $this->dispatch('success', ['message' => 'Queued — the server applies it within a minute']);
    }

    /**
     * Point a domain (the tenant's own subdomain or the client's domain) at
     * this tenant; the next tenant:server-sync writes its site and certificate.
     */
    public function saveCustomDomain(): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);

        $tenant = Tenant::findOrFail($this->tenantId);
        $this->validate([
            'customDomain' => ['required', ...Tenant::rules($tenant->id)['domain']],
        ], [
            'customDomain.required' => 'Enter the domain that points to this server',
            'customDomain.regex' => 'Enter a host name such as shop.example.com',
            'customDomain.not_in' => 'That is the main app address, which already has its own site',
            'customDomain.unique' => 'Another tenant already uses this domain',
        ]);

        $tenant->domain = $this->customDomain;
        if (! $tenant->hasCustomDomain()) {
            $this->addError('customDomain', 'This address is already served by the wildcard site — nothing to set up');

            return;
        }
        $tenant->save();

        $this->customDomain = '';
        $this->dispatch('success', ['message' => "Saved — the server sets up {$tenant->domain} within a minute"]);
    }

    /**
     * Drop the domain; the next tenant:server-sync removes its nginx site.
     */
    public function removeCustomDomain(): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);

        Tenant::findOrFail($this->tenantId)->update(['domain' => null]);
        $this->dispatch('success', ['message' => 'Domain removed — its nginx site is taken down within a minute']);
    }

    public function refreshAnalytics(): void
    {
        TenantAnalyticsService::forget($this->tenantId);
        $this->dispatch('success', ['message' => 'Analytics refreshed']);
    }

    public function runProvision(): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);

        $this->validate([
            'provision.name' => ['nullable', 'string', 'max:255'],
            'provision.email' => ['nullable', 'email', 'max:255'],
            'provision.password' => ['nullable', 'string', 'min:6'],
            'provision.system' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('modules.systems', [])))],
        ], [
            'provision.email.email' => 'Enter a valid admin email address',
            'provision.password.min' => 'The admin password must be at least 6 characters',
            'provision.system.in' => 'Choose one of the listed systems',
        ]);

        $response = (new ProvisionAction())->execute(
            $this->tenantId,
            ['name' => $this->provision['name'], 'email' => $this->provision['email'], 'password' => $this->provision['password']],
            $this->provision['system'] ?: null,
        );
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->provisionSteps = $response['steps'];
        $this->provision['password'] = '';
        TenantAnalyticsService::forget($this->tenantId);
        $this->dispatch('success', ['message' => $response['message']]);
    }

    public function render(TenantAnalyticsService $analytics, TenantSwitchService $switch, TenantServerService $server)
    {
        $tenant = Tenant::withTrashed()->findOrFail($this->tenantId);

        $settings = Configuration::withTenant($tenant->id)
            ->whereIn('key', ['active_module', 'company_name', 'base_currency_code', 'currency_code', 'mobile', 'email'])
            ->pluck('value', 'key');

        $users = isset($this->loaded_tabs['users'])
            ? User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->with('roles:id,name')->orderByDesc('is_admin')->orderBy('name')->limit(200)->get()
            : collect();

        $branches = collect();
        if (isset($this->loaded_tabs['branches'])) {
            $branches = Branch::withoutGlobalScopes()->where('tenant_id', $tenant->id)->orderBy('name')->get();
            $userCounts = UserHasBranch::withoutGlobalScopes()->whereIn('branch_id', $branches->pluck('id'))
                ->selectRaw('branch_id, COUNT(*) as total')->groupBy('branch_id')->pluck('total', 'branch_id');
            $branches->each(fn (Branch $branch) => $branch->setAttribute('users_count', (int) ($userCounts[$branch->id] ?? 0)));
        }

        return view('livewire.tenant.view', [
            'tenant' => $tenant,
            'settings' => $settings,
            'summary' => $analytics->summary($tenant),
            'users' => $users,
            'branches' => $branches,
            'switchUser' => $switch->targetUserFor($tenant),
            'isCurrentTenant' => $tenant->id === app(TenantService::class)->getCurrentTenantId(),
            'systems' => array_keys(config('modules.systems', [])),
            'serverHealth' => isset($this->loaded_tabs['server']) ? $server->health() : null,
            'nginxPreview' => isset($this->loaded_tabs['server']) && $tenant->hasCustomDomain() ? $server->renderSite($tenant, true) : null,
            'serverSetupCommand' => 'cd '.config('tenant_server.app_path').' && sudo '.config('tenant_server.php_binary').' artisan tenant:server-sync --shared',
        ]);
    }
}
