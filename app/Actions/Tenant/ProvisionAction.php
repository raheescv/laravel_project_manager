<?php

namespace App\Actions\Tenant;

use App\Actions\User\BranchAction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Configuration;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkingDay;
use App\Services\TenantService;
use App\Support\TenantCache;
use Database\Seeders\AccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WorkingDaySeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Gives a tenant the base data it needs to be usable: permissions, the Admin
 * role, a main branch, the chart of accounts, units, working days, neutral
 * configuration defaults, the default users (UserSeeder) and (optionally)
 * its own first admin user.
 *
 * Every step is idempotent — it only fills what is missing — so running it
 * again on a live tenant is safe and reports "already present". Tenant
 * wording (phone numbers, receipt footers, company details) is deliberately
 * NOT seeded: the tenant fills those in Settings.
 */
class ProvisionAction
{
    /**
     * @param  array{name?: ?string, email?: ?string, password?: ?string}  $admin
     * @return array{success: bool, message: string, steps?: list<array{key: string, label: string, created: int, status: string}>}
     */
    public function execute(int $tenantId, array $admin = [], ?string $system = null): array
    {
        $tenantService = app(TenantService::class);
        $previousTenant = $tenantService->getCurrentTenant();

        try {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                throw new \Exception("Tenant not found with the specified ID: $tenantId.", 1);
            }
            if ($system && ! array_key_exists($system, config('modules.systems', []))) {
                throw new \Exception("Unknown system: $system.", 1);
            }

            // Every tenant-scoped model below must read and write THIS tenant,
            // not the super admin's own — TenantScope and BelongsToTenant both
            // resolve through the current tenant.
            $tenantService->setCurrentTenant($tenant);

            $steps = DB::transaction(function () use ($tenant, $admin, $system): array {
                $steps = [];
                $steps[] = $this->step('permissions', 'Permissions', fn () => $this->countFor(Permission::query(), $tenant->id), fn () => $this->runSeeder(PermissionSeeder::class, $tenant->id));
                $steps[] = $this->step('role', 'Admin role', fn () => $this->adminRolePermissionCount($tenant->id), fn () => $this->grantAdminRole($tenant->id));
                $steps[] = $this->step('branch', 'Main branch', fn () => Branch::withTenant($tenant->id)->count(), fn () => $this->ensureBranch($tenant->id));
                $steps[] = $this->step('accounts', 'Chart of accounts', fn () => Account::withTenant($tenant->id)->count(), fn () => $this->runSeeder(AccountSeeder::class, $tenant->id));
                $steps[] = $this->step('units', 'Units', fn () => Unit::withTenant($tenant->id)->count(), fn () => $this->runSeeder(UnitSeeder::class, $tenant->id));
                $steps[] = $this->step('working_days', 'Working days', fn () => WorkingDay::withTenant($tenant->id)->count(), fn () => $this->runSeeder(WorkingDaySeeder::class, $tenant->id));
                $steps[] = $this->step('configuration', 'Configuration defaults', fn () => Configuration::withTenant($tenant->id)->count(), fn () => $this->seedConfiguration($tenant->id, $system));
                $steps[] = $this->step('users', 'Default users', fn () => User::withTenant($tenant->id)->count(), fn () => $this->runSeeder(UserSeeder::class, $tenant->id, ['withSuperAdmins' => false]));
                if (filled($admin['email'] ?? null)) {
                    $steps[] = $this->step('admin', 'Admin user', fn () => User::withTenant($tenant->id)->count(), fn () => $this->ensureAdmin($tenant->id, $admin));
                }

                return $steps;
            });

            foreach (['payment_methods', 'branches', 'accounts_slug_id_map', 'nav_order'] as $key) {
                TenantCache::forget($key);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $created = array_sum(array_column($steps, 'created'));
            $return['success'] = true;
            $return['message'] = $created ? "Provisioned {$tenant->name}: {$created} records added" : "{$tenant->name} already has every default";
            $return['steps'] = $steps;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        } finally {
            $previousTenant ? $tenantService->setCurrentTenant($previousTenant) : $tenantService->clearCurrentTenant();
        }

        return $return;
    }

    /**
     * @return array{key: string, label: string, created: int, status: string}
     */
    private function step(string $key, string $label, callable $count, callable $run): array
    {
        $before = $count();
        $run();
        $created = max(0, $count() - $before);

        return ['key' => $key, 'label' => $label, 'created' => $created, 'status' => $created ? 'created' : 'present'];
    }

    private function countFor($query, int $tenantId): int
    {
        return $query->where('tenant_id', $tenantId)->count();
    }

    /**
     * The seeders echo progress for the console; that output has no place in
     * a web response.
     *
     * @param  array<string, mixed>  $options  extra public seeder properties to set
     */
    private function runSeeder(string $seederClass, int $tenantId, array $options = []): void
    {
        $seeder = app($seederClass);
        $seeder->tenantId = $tenantId;
        foreach ($options as $property => $value) {
            $seeder->{$property} = $value;
        }

        ob_start();
        try {
            $seeder->run();
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Roles are per tenant (roles.tenant_id), so this tenant gets its own Admin
     * role holding its own permission rows. Given, never synced: a role an
     * earlier run or the tenant itself extended keeps what it has.
     */
    private function grantAdminRole(int $tenantId): void
    {
        $this->adminRole($tenantId)->givePermissionTo(Permission::where('tenant_id', $tenantId)->get());
    }

    private function adminRole(int $tenantId): Role
    {
        return Role::firstOrCreate(['tenant_id' => $tenantId, 'name' => 'Admin', 'guard_name' => 'web']);
    }

    private function adminRolePermissionCount(int $tenantId): int
    {
        $role = Role::where('tenant_id', $tenantId)->where('name', 'Admin')->where('guard_name', 'web')->first();

        return $role ? $role->permissions()->count() : 0;
    }

    private function ensureBranch(int $tenantId): void
    {
        if (! Branch::withTenant($tenantId)->exists()) {
            Branch::create(['tenant_id' => $tenantId, 'name' => 'Main', 'code' => 'M']);
        }
    }

    /**
     * Operational defaults only — values that point at this tenant's own rows
     * (payment method accounts, purchase branch) are resolved here rather than
     * copied, and existing keys are never overwritten.
     */
    private function seedConfiguration(int $tenantId, ?string $system): void
    {
        $branchId = Branch::withTenant($tenantId)->orderBy('id')->value('id');
        $paymentMethodIds = Account::withTenant($tenantId)->whereIn('slug', ['cash', 'card'])->orderBy('id')->pluck('id')->all();

        $defaults = [
            'barcode_type' => 'product_wise',
            'default_status' => 'completed',
            'sale_type' => 'pos',
            'default_product_type' => 'product',
            'sale_item_row_mode' => 'separate',
            'purchase_item_row_mode' => 'merge',
            'prevent_out_of_stock_sales' => 'no',
            'hide_out_of_stock_sale_items' => 'no',
            'print_item_label' => 'product',
            'enable_discount_in_print' => 'yes',
            'enable_total_quantity_in_print' => 'yes',
            'enable_logo_in_print' => 'yes',
            'enable_barcode_in_print' => 'yes',
            'payment_methods' => json_encode($paymentMethodIds),
            'default_purchase_branch_id' => json_encode(array_filter([$branchId])),
        ];
        if ($system) {
            $defaults['active_module'] = $system;
        }

        foreach ($defaults as $key => $value) {
            Configuration::firstOrCreate(['tenant_id' => $tenantId, 'key' => $key], ['value' => $value]);
        }

        if ($system) {
            Configuration::withTenant($tenantId)->where('key', 'active_module')->update(['value' => $system]);
        }
    }

    /**
     * @param  array{name?: ?string, email?: ?string, password?: ?string}  $admin
     */
    private function ensureAdmin(int $tenantId, array $admin): void
    {
        $branchId = Branch::withTenant($tenantId)->orderBy('id')->value('id');

        $user = User::withTenant($tenantId)->where('email', $admin['email'])->first();
        if (! $user) {
            if (blank($admin['password'] ?? null)) {
                throw new \Exception('A password is required to create the admin user.', 1);
            }
            $user = User::create([
                'tenant_id' => $tenantId,
                'name' => $admin['name'] ?: 'Admin',
                'email' => $admin['email'],
                'password' => $admin['password'],
                'default_branch_id' => $branchId,
            ]);
        }

        $this->markAdmin($user);
        $user->assignRole($this->adminRole($tenantId));

        if ($branchId) {
            (new BranchAction())->execute($user->id, [$branchId], BranchAction::MODE_APPEND, $user->default_branch_id ?: $branchId);
        }
    }

    /** `is_admin` is stripped from mass assignment by the user actions, so it is set explicitly. */
    private function markAdmin(Model $user): void
    {
        $user->forceFill(['is_admin' => true, 'is_active' => true])->save();
    }
}
