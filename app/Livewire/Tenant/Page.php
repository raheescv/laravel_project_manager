<?php

namespace App\Livewire\Tenant;

use App\Actions\Tenant\CreateAction;
use App\Actions\Tenant\UpdateAction;
use App\Models\Tenant;
use Faker\Factory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Tenant-Page-Create-Component' => 'create',
        'Tenant-Page-Update-Component' => 'edit',
    ];

    public $tenants = [];

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleTenantModal');
    }

    public function edit($id = null)
    {
        // Handle both direct ID and event payload format
        $tenantId = is_array($id) ? ($id['id'] ?? null) : $id;
        $this->mount($tenantId);
        $this->dispatch('ToggleTenantModal');
    }

    public function mount($table_id = null)
    {
        // Check if user is super admin
        if (! Auth::user()->is_super_admin) {
            abort(403, 'Unauthorized access. Only super admin users can access this page.');
        }

        // Normalize empty string to null
        $this->table_id = $table_id ?: null;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            $code = '';
            $subdomain = '';
            $domain = '';
            if (! app()->isProduction()) {
                $name = $faker->company();
                $code = strtoupper($faker->lexify('???'));
                $subdomain = $faker->slug();
                $domain = $faker->domainName();
            }
            $this->tenants = [
                'name' => $name,
                'code' => $code,
                'subdomain' => $subdomain,
                'domain' => $domain,
                'is_active' => true,
                'description' => '',
            ];
        } else {
            $tenant = Tenant::withoutGlobalScopes()->find($this->table_id);
            if (! $tenant) {
                $this->dispatch('error', ['message' => 'Tenant not found']);

                return;
            }
            $this->tenants = $tenant->toArray();
        }
    }

    protected function rules()
    {
        $tenantRules = Tenant::rules($this->table_id ?? 0);
        $rules = [];
        foreach ($tenantRules as $key => $rule) {
            $rules["tenants.{$key}"] = $rule;
        }
        $rules['tenants.description'] = ['nullable', 'string'];

        return $rules;
    }

    protected $messages = [
        'tenants.name.required' => 'The name field is required',
        'tenants.code.required' => 'The code field is required',
        'tenants.code.unique' => 'The code is already registered',
        'tenants.subdomain.required' => 'The subdomain field is required',
        'tenants.subdomain.unique' => 'The subdomain is already registered',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->tenants);
            } else {
                $response = (new UpdateAction())->execute($this->tenants, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleTenantModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshTenantTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.tenant.page');
    }
}
