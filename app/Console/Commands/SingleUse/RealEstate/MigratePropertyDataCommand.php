<?php

namespace App\Console\Commands\SingleUse\RealEstate;

use App\Enums\RentOut\AgreementType;
use App\Jobs\BranchProductCreationJob;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Configuration;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MigratePropertyDataCommand extends Command
{
    protected $signature = 'migrate:property-data {--tenant= : Tenant ID to assign} {--dry-run : Run without inserting data}';

    protected $description = 'Migrate property, maintenance, asset, and supply data from accounts (mysql2) to project manager, preserving primary keys';

    private int $tenantId;

    private bool $dryRun;

    /**
     * Map old integer status values to new string enum values
     */
    private array $propertyStatusMap = [
        1 => 'vacant',
        2 => 'occupied',
        3 => 'booked',
    ];

    private array $propertyFlagMap = [
        1 => 'active',
        2 => 'disabled',
    ];

    private array $buildingOwnershipMap = [
        1 => 'own',
        2 => 'lease',
        3 => 'rent',
    ];

    private array $rentOutStatusMap = [
        1 => 'occupied',
        2 => 'vacated',
        3 => 'expired',
        4 => 'booked',
        5 => 'cancelled',
    ];

    private array $chequeStatusMap = [
        1 => 'uncleared',
        2 => 'submitted',
        3 => 'return',
        4 => 'bounce',
        5 => 'cleared',
        6 => 'terminated',
    ];

    private array $securityStatusMap = [
        'Submitted' => 'pending',
        'Collected' => 'collected',
        'Returned' => 'returned',
        'Adjusted' => 'adjusted',
        'Pending' => 'pending',
    ];

    private array $assetSupplyStatusMap = [
        'Requirement' => 'requirement',
        'Approved' => 'approved',
        'Rejected' => 'rejected',
        'Collected' => 'collected',
        'Final Approved' => 'final_approved',
        'Completed' => 'completed',
        'Expired' => 'expired',
    ];

    private array $maintenanceStatusMap = [
        'pending' => 'pending',
        'completed' => 'completed',
        'rejected' => 'cancelled',
        'cancelled' => 'cancelled',
    ];

    private array $priorityMap = [
        'Low' => 'low',
        'Medium' => 'medium',
        'High' => 'high',
        'Critical' => 'critical',
        'low' => 'low',
        'medium' => 'medium',
        'high' => 'high',
        'critical' => 'critical',
    ];

    private array $segmentMap = [
        'PPMC' => 'ppmc',
        'Corrective' => 'corrective',
        'Preparation' => 'preparation',
        'ppmc' => 'ppmc',
        'corrective' => 'corrective',
        'preparation' => 'preparation',
    ];

    private array $maintenanceComplaintStatusMap = [
        'pending' => 'pending',
        'assigned' => 'assigned',
        'completed' => 'completed',
        'outstanding' => 'outstanding',
        'paid' => 'completed',
        'cancelled' => 'cancelled',
    ];

    private array $paymentModeMap = [];

    /**
     * Lazily-built lookup maps. Each replaces a per-row query that would
     * otherwise fire once for every source record (tens of thousands of times).
     */
    private ?array $branchByRentout = null;      // source rentouts.id => branch_id

    private ?array $accountByRef = null;         // target accounts.second_reference_no => id

    private ?array $userByRef = null;            // target users(type=user).second_reference_no => id

    private ?array $groupByBuilding = null;      // source property_buildings.id => property_group_id

    private ?array $propertyMetaById = null;     // target properties.id => {group,building,type}

    private ?array $productByRef = null;         // target products(type=product).second_reference_no => id

    private ?array $supplyRequestIds = null;     // target supply_requests.id set

    private ?array $maintenanceBranchCache = null; // target maintenances.id => branch_id

    public function handle(): int
    {
        $this->tenantId = (int) ($this->option('tenant') ?: 1);
        $this->dryRun = (bool) $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('DRY RUN MODE - No data will be inserted.');
        }

        $this->info("Starting property data migration (tenant_id: {$this->tenantId})...");

        // Build payment mode map: old payment_mode_id -> new string value
        $this->buildPaymentModeMap();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            Configuration::updateOrCreate(['tenant_id' => 1, 'key' => 'active_module'], ['value' => 'Property Management Module']);
            $this->rolesAndPermissions();
            $this->migrateUsers();
            $this->assignUserRoles();
            $this->migrateAccountHeads();
            $this->migrateCustomers();
            $this->migrateVendors();

            $this->migratePropertyGroups();
            $this->migratePropertyBuildings();
            $this->migratePropertyTypes();
            $this->migrateProperties();

            $this->migrateRentOuts();
            $this->migrateUtilities();

            $this->migrateRentOutPaymentTerms();
            $this->migrateRentOutCheques();

            $this->migrateRentOutUtilityTerms();

            $this->migrateRentOutTransactions();

            $this->migrateRentOutSecurities();
            $this->migrateRentOutExtends();
            $this->migrateRentOutServices();
            $this->migrateRentOutNotes();

            $this->migrateDocumentTypes();
            $this->migrateRentOutDocuments();

            $this->migrateTenantDetails();

            $this->migratePropertyLeads();
            $this->migratePropertyAssets();
            $this->migrateSupplyRequests();
            $this->migrateSupplyRequestItems();
            $this->migrateSupplyRequestNotes();
            $this->migrateSupplyRequestImages();
            $this->migrateComplaintCategories();
            $this->migrateComplaints();
            $this->migrateMaintenances();
            $this->migrateMaintenanceComplaints();
        } catch (\Exception $e) {
            $this->error("Migration failed: {$e->getMessage()}");
            Log::error('Property data migration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return Command::FAILURE;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->newLine();
        $this->info('Property data migration completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Migrate a source collection into a target table.
     *
     * The $mapper receives each source row and returns the target row array,
     * or null to skip it. Progress bar, dry-run guard, skip counting and the
     * summary line (with an accurate inserted-vs-skipped count) are handled here.
     *
     * @param  callable(object):?array  $mapper
     * @param  callable(array):void|null  $writer  Custom write; defaults to updateOrInsert keyed on $uniqueBy.
     */
    private function migrateTable(
        string $noun,
        Collection $records,
        string $target,
        callable $mapper,
        array $uniqueBy = ['id'],
        ?callable $writer = null
    ): void {
        if ($records->isEmpty()) {
            $this->warn("No {$noun} to migrate.");

            return;
        }

        $this->info("Migrating {$noun}...");
        $bar = $this->output->createProgressBar($records->count());
        $inserted = 0;
        $skipped = 0;

        foreach ($records as $row) {
            $data = $mapper($row);

            if ($data === null) {
                $skipped++;
                $bar->advance();

                continue;
            }

            if (! $this->dryRun) {
                $writer
                    ? $writer($data)
                    : DB::table($target)->updateOrInsert(Arr::only($data, $uniqueBy), $data);
            }

            $inserted++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$inserted} {$noun}.".($skipped ? " Skipped {$skipped}." : ''));
    }

    private function insertOrIgnoreInto(string $table): callable
    {
        return fn (array $data) => DB::table($table)->insertOrIgnore($data);
    }

    // --- Lazily-built lookup maps -------------------------------------------

    private function branchForRentout($rentoutId): int
    {
        $this->branchByRentout ??= DB::connection('mysql2')->table('rentouts')
            ->pluck('branch_id', 'id')->all();

        return (int) ($this->branchByRentout[$rentoutId] ?? 1);
    }

    private function accountId($ref): ?int
    {
        if (! $ref) {
            return null;
        }

        if ($this->accountByRef === null) {
            $this->accountByRef = [];
            foreach (DB::table('accounts')->where('tenant_id', $this->tenantId)->orderBy('id')->get(['id', 'second_reference_no']) as $a) {
                if ($a->second_reference_no !== null && ! isset($this->accountByRef[$a->second_reference_no])) {
                    $this->accountByRef[$a->second_reference_no] = $a->id;
                }
            }
        }

        return $this->accountByRef[$ref] ?? null;
    }

    private function userId($ref): ?int
    {
        if (! $ref) {
            return null;
        }

        if ($this->userByRef === null) {
            $this->userByRef = [];
            foreach (DB::table('users')->where('type', 'user')->orderBy('id')->get(['id', 'second_reference_no']) as $u) {
                if ($u->second_reference_no !== null && ! isset($this->userByRef[$u->second_reference_no])) {
                    $this->userByRef[$u->second_reference_no] = $u->id;
                }
            }
        }

        return $this->userByRef[$ref] ?? null;
    }

    private function groupForBuilding($buildingId): int
    {
        $this->groupByBuilding ??= DB::connection('mysql2')->table('property_buildings')
            ->pluck('property_group_id', 'id')->all();

        return (int) ($this->groupByBuilding[$buildingId] ?? 0);
    }

    private function propertyMeta($propertyId): ?object
    {
        if ($this->propertyMetaById === null) {
            $this->propertyMetaById = DB::table('properties')
                ->get(['id', 'property_group_id', 'property_building_id', 'property_type_id'])
                ->keyBy('id')->all();
        }

        return $this->propertyMetaById[$propertyId] ?? null;
    }

    private function productId($ref): ?int
    {
        if (! $ref) {
            return null;
        }

        if ($this->productByRef === null) {
            $this->productByRef = [];
            foreach (DB::table('products')->where('type', 'product')->whereNotNull('second_reference_no')->orderBy('id')->get(['id', 'second_reference_no']) as $p) {
                if (! isset($this->productByRef[$p->second_reference_no])) {
                    $this->productByRef[$p->second_reference_no] = $p->id;
                }
            }
        }

        return $this->productByRef[$ref] ?? null;
    }

    private function supplyRequestExists($id): bool
    {
        $this->supplyRequestIds ??= DB::table('supply_requests')->pluck('id')->flip()->all();

        return isset($this->supplyRequestIds[$id]);
    }

    private function maintenanceBranchMap(): array
    {
        return $this->maintenanceBranchCache ??= DB::table('maintenances')->pluck('branch_id', 'id')->all();
    }

    // --- Migrations ----------------------------------------------------------

    private function buildPaymentModeMap(): void
    {
        // In accounts project, payment modes are stored as account_head IDs
        // Map common payment mode IDs to string values
        // This needs to be customized based on the actual account_heads in the accounts DB
        $paymentModes = DB::connection('mysql2')
            ->table('account_heads')
            ->whereIn('account_category_id', [16, 17])
            ->select('id', 'name')
            ->get();

        $unrecognised = [];

        foreach ($paymentModes as $mode) {
            $name = strtolower($mode->name);
            if (str_contains($name, 'cash')) {
                $this->paymentModeMap[$mode->id] = 'cash';
            } elseif (str_contains($name, 'cheque') || str_contains($name, 'check')) {
                $this->paymentModeMap[$mode->id] = 'cheque';
            } elseif (str_contains($name, 'pos') || str_contains($name, 'card')) {
                $this->paymentModeMap[$mode->id] = 'pos';
            } elseif (str_contains($name, 'bank') || str_contains($name, 'transfer')) {
                $this->paymentModeMap[$mode->id] = 'bank_transfer';
            } else {
                // Still a payment head, so it stays in the map and keeps its own
                // account - but the name told us nothing, so say so rather than
                // letting it pass as cash.
                $this->paymentModeMap[$mode->id] = 'other';
                $unrecognised[] = "{$mode->id} ({$mode->name})";
            }
        }

        $this->info('Payment mode map built: '.count($this->paymentModeMap).' modes mapped.');

        if ($unrecognised) {
            $this->warn('Payment heads with an unrecognised name: '.implode(', ', $unrecognised));
        }
    }

    private function resolvePaymentMode(?int $modeId): string
    {
        if (! $modeId) {
            return 'cash';
        }

        return $this->paymentModeMap[$modeId] ?? 'cash';
    }

    private function migrateUsers(): void
    {
        $users = DB::connection('mysql2')->table('users')->get();
        $this->migrateTable('users', $users, 'users', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'type' => 'user',
            'name' => $row->name,
            'code' => $row->code ?? null,
            'email' => $row->email,
            'mobile' => $row->mobile ?? null,
            'is_admin' => $row->is_admin ?? 0,
            'default_branch_id' => $row->default_branch_id ?? null,
            'designation_id' => $row->designation_id ?? null,
            'order_no' => $row->order_no ?? 1,
            'email_verified_at' => $row->email_verified_at ?? null,
            'password' => $row->password,
            'pin' => $row->pin ?? null,
            'dob' => ($row->dob ?? null) !== '0000-00-00' ? ($row->dob ?? null) : null,
            'doj' => ($row->doj ?? null) !== '0000-00-00' ? ($row->doj ?? null) : null,
            'place' => $row->place ?? null,
            'nationality' => $row->nationality ?? null,
            'allowance' => $row->allowance ?? null,
            'salary' => $row->salary ?? null,
            'hra' => $row->hra ?? null,
            'max_discount_per_sale' => $row->max_discount_per_sale ?? 100,
            'is_locked' => $row->is_locked ?? 0,
            'is_active' => $row->is_active ?? 1,
            'second_reference_no' => $row->id,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);

        // Migrate employees from old employees table as type='employee'
        $employees = DB::connection('mysql2')->table('employees')->get();
        $this->migrateTable('employees as users', $employees, 'users', fn ($row) => [
            'tenant_id' => $this->tenantId,
            'type' => 'employee',
            'name' => $row->name,
            'code' => $row->code ?? null,
            'email' => $row->email ?? $row->name.'@employee.local',
            'mobile' => $row->mobile ?? null,
            'is_admin' => 0,
            'default_branch_id' => $row->branch_id ?? null,
            'designation_id' => $row->designation_id ?? null,
            'order_no' => $row->order_no ?? 1,
            'password' => $row->password ?? bcrypt('password'),
            'pin' => $row->pin ?? null,
            'dob' => ($row->dob ?? null) !== '0000-00-00' ? ($row->dob ?? null) : null,
            'doj' => ($row->doj ?? null) !== '0000-00-00' ? ($row->doj ?? null) : null,
            'place' => $row->place ?? null,
            'nationality' => $row->nationality ?? null,
            'allowance' => $row->allowance ?? null,
            'salary' => $row->salary ?? null,
            'hra' => $row->hra ?? null,
            'is_locked' => $row->is_locked ?? 0,
            'is_active' => $row->is_active ?? 1,
            'second_reference_no' => 'emp_'.$row->id,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ], ['tenant_id', 'email']);
    }

    /**
     * Migrate roles and their permission grants.
     *
     * Old accounts DB models access as:
     *   user_types              -> roles assigned to users     (has freeze=1 for built-in admin types)
     *   designations            -> roles assigned to employees (no privilege source of their own)
     *   project_modules         -> module / sub_module catalogue
     *   user_type_privileges    -> (user_type_id, project_module_id, action) grants; action ∈ view/create/edit/delete
     *
     * New DB uses Spatie with a different, richer permission taxonomy (e.g. "sale.create",
     * "account category.view"). The two vocabularies only partially overlap, so:
     *   - Every old role name is created as a Spatie role (guard "web").
     *   - freeze=1 user_types (Super Admin / Admin) are treated as full-access and get ALL permissions.
     *   - Every other user_type gets a best-effort mapping: each old "<sub_module|module>.<action>"
     *     slug that matches an existing new permission name (case-insensitive) is granted; the rest
     *     are logged and skipped (they name features this app doesn't have).
     *   - designation roles have no privilege source, so they are created without permissions.
     */
    private function rolesAndPermissions(): void
    {
        $this->info('Migrating roles and permissions...');

        $old = DB::connection('mysql2');

        // actualName keyed by lowercased name, so old slugs (always lowercased) can resolve to the
        // real, correctly-cased new permission name that givePermissionTo() expects.
        $newPermissionByLower = Permission::pluck('name')
            ->mapWithKeys(fn ($name) => [strtolower($name) => $name])
            ->all();
        $allPermissionNames = array_values($newPermissionByLower);

        $modules = $old->table('project_modules')->get()->keyBy('id');

        // Pre-group every user_type's privileges into the set of new-permission names it should get.
        $mappedByUserType = [];
        $unmatchedSlugs = [];
        $privileges = $old->table('user_type_privileges')->whereNull('deleted_at')->get();
        foreach ($privileges as $privilege) {
            $module = $modules[$privilege->project_module_id] ?? null;
            if (! $module) {
                continue;
            }
            $action = strtolower($privilege->action);
            // Try the specific sub_module first, then the broader module, as the permission subject.
            foreach ([$module->sub_module, $module->module] as $subject) {
                $slug = strtolower($subject).'.'.$action;
                if (isset($newPermissionByLower[$slug])) {
                    $mappedByUserType[$privilege->user_type_id][$newPermissionByLower[$slug]] = true;

                    continue 2;
                }
            }
            $unmatchedSlugs[strtolower($module->sub_module).'.'.$action] = true;
        }

        // Roles from user_types (assigned to migrated users).
        $userTypes = $old->table('user_types')->whereNull('deleted_at')->get();
        foreach ($userTypes as $userType) {
            $role = Role::firstOrCreate(['name' => $userType->name, 'guard_name' => 'web']);

            if ($userType->freeze == 1) {
                // Built-in admin type: full access.
                $role->syncPermissions($allPermissionNames);
                $this->info("Role '{$userType->name}': granted ALL ".count($allPermissionNames).' permissions (admin type).');
            } else {
                $names = array_keys($mappedByUserType[$userType->id] ?? []);
                $role->syncPermissions($names);
                $this->info("Role '{$userType->name}': granted ".count($names).' mapped permission(s).');
            }
        }

        // Roles from designations (assigned to migrated employees). No privilege source to map from.
        $designations = $old->table('designations')->whereNull('deleted_at')->get();
        foreach ($designations as $designation) {
            Role::firstOrCreate(['name' => $designation->name, 'guard_name' => 'web']);
        }

        if ($unmatchedSlugs) {
            $this->warn(count($unmatchedSlugs).' old privilege slug(s) had no matching new permission and were skipped (see log).');
            Log::info('Roles/permissions migration — unmatched old privilege slugs: '.implode(', ', array_keys($unmatchedSlugs)));
        }

        // Drop Spatie's cached permission map so the assignRole() calls that follow (and the
        // rest of the app) see these fresh roles/grants.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Roles and permissions migration completed.');
    }

    /**
     * Attach the migrated roles to the migrated users/employees.
     *
     * rolesAndPermissions() only creates the roles; migrateUsers() writes user rows with raw
     * inserts and cannot assignRole() inline, so the two are joined here:
     *   - users:     users.user_role_id  -> user_types.name   (migrated user keeps its source id)
     *   - employees: employees.designation_id -> designations.name (matched by second_reference_no)
     */
    private function assignUserRoles(): void
    {
        if ($this->dryRun) {
            $this->info('Would assign roles to migrated users/employees.');

            return;
        }

        $this->info('Assigning roles to migrated users/employees...');

        $old = DB::connection('mysql2');
        $userTypeNames = $old->table('user_types')->pluck('name', 'id');
        $designationNames = $old->table('designations')->pluck('name', 'id');

        $assigned = 0;

        // Users keep their source id (migrateUsers uses id => row->id).
        foreach ($old->table('users')->get(['id', 'user_role_id']) as $row) {
            $roleName = $userTypeNames[$row->user_role_id] ?? null;
            if (! $roleName) {
                continue;
            }

            $user = User::find($row->id);
            if ($user && ! $user->hasRole($roleName)) {
                Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                $user->assignRole($roleName);
                $assigned++;
            }
        }

        // Employees are keyed by second_reference_no = 'emp_'.<source id>.
        foreach ($old->table('employees')->get(['id', 'designation_id']) as $row) {
            $roleName = $designationNames[$row->designation_id] ?? null;
            if (! $roleName) {
                continue;
            }

            $user = User::where('second_reference_no', 'emp_'.$row->id)->first();
            if ($user && ! $user->hasRole($roleName)) {
                Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                $user->assignRole($roleName);
                $assigned++;
            }
        }

        $this->info("Assigned roles to {$assigned} user(s)/employee(s).");
    }

    private function migrateAccountHeads(): void
    {
        // Migrate payment mode accounts (asset accounts like Cash, Bank, etc.)
        $heads = DB::connection('mysql2')
            ->table('account_heads')
            ->whereIn('account_category_id', [16, 17])
            ->get();

        $this->migrateTable('account heads', $heads, 'accounts', function ($row) {
            $name = ucfirst(strtolower($row->name));

            return [
                'tenant_id' => $this->tenantId,
                'account_type' => 'asset',
                'name' => $name,
                'slug' => Str::slug($name),
                'second_reference_no' => $row->id,
            ];
        }, ['tenant_id', 'account_type', 'name'], fn ($data) => Account::updateOrCreate(
            Arr::only($data, ['tenant_id', 'account_type', 'name']),
            $data
        ));
    }

    private function migrateCustomers(): void
    {
        $customers = DB::connection('mysql2')
            ->table('customers')
            ->join('account_heads', 'customers.account_head_id', '=', 'account_heads.id')
            ->where('account_heads.id', '!=', 2)
            ->select('customers.*', 'customers.account_head_id', 'account_heads.name as name')
            ->get();

        $this->migrateTable('customers', $customers, 'accounts', function ($row) {
            $name = explode('@', $row->name);

            return [
                'tenant_id' => $this->tenantId,
                'account_type' => 'asset',
                'second_reference_no' => $row->account_head_id,
                'model' => 'customer',
                'name' => ucfirst(strtolower($name[0])),
                'email' => $row->email ?? null,
                'mobile' => $row->mobile ?? null,
                'whatsapp_mobile' => $row->whatsapp_no ?? null,
                'nationality' => $this->normalizeNationality($row->nationality ?? null),
                'dob' => ($row->dob ?? null) !== '0000-00-00' ? ($row->dob ?? null) : null,
                'id_no' => $row->id_no ?? null,
                'company' => $row->company ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }, writer: $this->insertOrIgnoreInto('accounts'));
    }

    private function migrateVendors(): void
    {
        $vendors = DB::connection('mysql2')
            ->table('vendors')
            ->join('account_heads', 'vendors.account_head_id', '=', 'account_heads.id')
            ->select('vendors.*', 'vendors.account_head_id', 'account_heads.name as name')
            ->get();

        $this->migrateTable('vendors', $vendors, 'accounts', fn ($row) => [
            'tenant_id' => $this->tenantId,
            'account_type' => 'liability',
            'second_reference_no' => $row->account_head_id,
            'model' => 'vendor',
            'name' => ucfirst(strtolower($row->name)),
            'email' => $row->email ?? null,
            'mobile' => $row->mobile ?? null,
            'place' => $row->place ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ], writer: $this->insertOrIgnoreInto('accounts'));
    }

    private function normalizeNationality(?string $nationality): ?string
    {
        if (! $nationality) {
            return null;
        }

        return match (strtolower(trim($nationality))) {
            'indian/tamel', 'indian/kerala', 'kerala', 'keral', 'indian' => 'India',
            'qatari', 'qatary', 'qatar' => 'Qatar',
            'egyptian', 'egyp' => 'Egypt',
            'nigeria' => 'Nigeria',
            'moroccan' => 'Morocco',
            'philipines' => 'Philippines',
            'saudi' => 'Saudi Arabia',
            'tunisian' => 'Tunisia',
            'seria' => 'Syria',
            'pakistanis' => 'Pakistan',
            default => $nationality,
        };
    }

    private function migratePropertyGroups(): void
    {
        $records = DB::connection('mysql2')->table('property_groups')->get();

        $this->migrateTable('property groups', $records, 'property_groups', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'branch_id' => $row->branch_id ?? 1,
            'name' => $row->name,
            'arabic_name' => $row->arabic_name ?? null,
            'description' => null,
            'lease_agreement_years' => $row->lease_agreement_years ?? null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migratePropertyBuildings(): void
    {
        $records = DB::connection('mysql2')->table('property_buildings')->get();

        $this->migrateTable('property buildings', $records, 'property_buildings', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'branch_id' => $row->branch_id ?? 1,
            'property_group_id' => $row->property_group_id,
            'name' => $row->name,
            'arabic_name' => $row->name_arabic ?? null,
            'created_date' => $row->date_created ?? null,
            'reference_code' => $row->reference_code ?? null,
            'building_no' => $row->building_no ?? null,
            'location' => $row->location ?? null,
            'floors' => $row->floors ?? null,
            'investment' => $row->investment ?? null,
            'electricity' => $row->electricity ?? null,
            'road' => $row->road ?? null,
            'landmark' => $row->landmark ?? null,
            'amount' => $row->amount ?? null,
            'ownership' => $this->buildingOwnershipMap[$row->owner ?? 1] ?? 'own',
            'status' => $row->status ?? 'active',
            'account_id' => $this->accountId($row->account_head_id),
            'remark' => $row->remark ?? null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migratePropertyTypes(): void
    {
        $records = DB::connection('mysql2')->table('property_types')->get();

        $this->migrateTable('property types', $records, 'property_types', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'name' => $row->name,
            'description' => null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function migrateProperties(): void
    {
        $records = DB::connection('mysql2')->table('properties')->get();

        $this->migrateTable('properties', $records, 'properties', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'branch_id' => $row->branch_id ?? 1,
            'property_group_id' => $this->groupForBuilding($row->property_building_id),
            'property_building_id' => $row->property_building_id,
            'property_type_id' => $row->property_type_id ?? null,
            'number' => $row->number ?? null,
            'code' => $row->code ?? null,
            'unit_no' => null,
            'floor' => $row->floor ?? null,
            'rooms' => $row->rooms ?? null,
            'kitchen' => $row->kitchen ?? null,
            'toilet' => $row->toilet ?? null,
            'hall' => $row->hall ?? null,
            'size' => $row->size ?? null,
            'rent' => $row->rent ?? 0,
            'ownership' => $row->ownership ?? null,
            'electricity' => $row->electricity ?? null,
            'kahramaa' => $row->kahramaa ?? null,
            'parking' => $row->parking ?? null,
            'furniture' => $row->furniture ?? null,
            'status' => $this->propertyStatusMap[$row->status ?? 1] ?? 'vacant',
            'availability_status' => $row->availability_status ?? 'available',
            'flag' => $this->propertyFlagMap[$row->flag ?? 1] ?? 'active',
            'remark' => $row->remark ?? null,
            'floor_plan' => $row->floor_plan ?? null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migrateRentOuts(): void
    {
        $records = DB::connection('mysql2')->table('rentouts')->get();

        $this->migrateTable('rent outs', $records, 'rent_outs', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'branch_id' => $row->branch_id ?? 1,
            'property_id' => $row->property_id,
            'property_building_id' => $row->property_building_id ?? 0,
            'property_type_id' => $row->property_type_id ?? null,
            'property_group_id' => $row->property_group_id ?? 0,
            'account_id' => $this->accountId($row->customer_id),
            'salesman_id' => $row->salesman_id ?? null,
            'agreement_type' => $row->agreement_type ?? 'rental',
            'booking_type' => $row->booking_type ?? null,
            'status' => $this->rentOutStatusMap[$row->status ?? 1] ?? 'occupied',
            'booking_status' => $row->booking_status ?? null,
            'start_date' => $row->start_date,
            'end_date' => $row->end_date,
            'vacate_date' => $row->vacate_date ?? null,
            'rent' => $row->rent ?? 0,
            'no_of_terms' => $row->no_of_terms ?? 1,
            'payment_frequency' => $row->payment_frequency ?? null,
            'discount' => $row->discount ?? 0,
            'free_month' => $row->free_month ?? 0,
            'total' => $row->total ?? 0,
            'collection_starting_day' => $row->collection_starting_day ?? 1,
            'collection_payment_mode' => $this->resolvePaymentMode($row->collection_payment_mode_id ?? null),
            'collection_bank_name' => $row->collection_bank_name ?? null,
            'collection_cheque_no' => $row->collection_cheque_no ?? null,
            'management_fee' => $row->management_fee ?? 0,
            'management_fee_payment_method_id' => $this->accountId($row->management_fee_payment_mode_id ?? null),
            'management_fee_remarks' => $row->management_fee_remarks ?? null,
            'down_payment' => $row->down_payment ?? 0,
            'down_payment_payment_method_id' => $this->accountId($row->down_payment_mode_id ?? null),
            'down_payment_remarks' => $row->down_payment_remarks ?? null,
            'include_electricity_water' => $row->include_electricity_water ?? null,
            'include_ac' => $row->include_ac ?? null,
            'include_wifi' => $row->include_wifi ?? null,
            'remark' => $row->remark ?? null,
            'cancellation_policy_ar' => $row->cancellation_policy_ar ?? null,
            'cancellation_policy_en' => $row->cancellation_policy_en ?? null,
            'payment_terms_ar' => $row->payment_terms_ar ?? null,
            'payment_terms_en' => $row->payment_terms_en ?? null,
            'payment_terms_extended_ar' => $row->payment_terms_extended_ar ?? null,
            'payment_terms_extended_en' => $row->payment_terms_extended_en ?? null,
            'mandatory_documents' => $row->mandatory_documents ?? null,
            'reservation_fees_disclaimer_en' => $row->reservation_fees_disclaimer_en ?? null,
            'reservation_fees_disclaimer_ar' => $row->reservation_fees_disclaimer_ar ?? null,
            'payment_term_rent' => $row->payment_term_rent ?? 0,
            'payment_term_discount' => $row->payment_term_discount ?? 0,
            'payment_term_total' => $row->payment_term_total ?? 0,
            'total_paid' => $row->total_paid ?? 0,
            'total_current_rent' => $row->total_current_rent ?? 0,
            'created_by' => $row->created_by ?? null,
            'submitted_by' => $row->submitted_by ?? null,
            'submitted_at' => $row->submitted_at ?? null,
            'approved_by' => $row->approved_by ?? null,
            'approved_at' => $row->approved_at ?? null,
            'financial_approved_by' => $row->financial_approved_by ?? null,
            'financial_approved_at' => $row->financial_approved_at ?? null,
            'completed_by' => $row->completed_by ?? null,
            'completed_at' => $row->completed_at ?? null,
            // Booked records (status=4) should not be soft-deleted
            'deleted_at' => ($row->status ?? 1) == 4 ? null : ($row->deleted_at ?? null),
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    /**
     * Move the old rentout journals - the money movements behind the Payment,
     * Services and Transactions tabs - into rent_out_transactions, carrying the
     * payment mode, cheque reference and voucher number, and posting the
     * matching double entry.
     *
     * Rent and utility accruals are deliberately left behind: TransactionsTab
     * derives those from the terms' due dates, so migrating them as well would
     * count every charge twice. Service charges have no term to derive from and
     * are kept as debit rows.
     *
     * Source journal ids become transaction and journal ids so the two systems
     * stay reconcilable row for row, and re-running only overwrites its own rows.
     */
    private function migrateRentOutTransactions(): void
    {
        $records = DB::connection('mysql2')->table('journals')
            ->whereNull('deleted_at')
            ->where('rentout_id', '>', 0)
            ->orderBy('id')
            ->get();

        $termDueDates = DB::connection('mysql2')->table('payment_terms')->pluck('date', 'id');
        $utilityDates = DB::connection('mysql2')->table('rentout_utility_terms')->pluck('date', 'id');
        $oldCheques = DB::connection('mysql2')->table('rentout_cheques')->get()->keyBy('id');
        $rentOuts = DB::table('rent_outs')->get(['id', 'account_id', 'branch_id', 'agreement_type'])->keyBy('id');

        $unmappedHeads = [];
        $journals = [];
        $entries = [];

        $this->migrateTable(
            'rent out transactions',
            $records,
            'rent_out_transactions',
            function ($row) use ($termDueDates, $utilityDates, $oldCheques, $rentOuts, &$unmappedHeads, &$journals, &$entries): ?array {
                $rentOut = $rentOuts[$row->rentout_id] ?? null;
                if (! $rentOut) {
                    return null;
                }

                $mode = $this->paymentHeadFor($row);
                $isServiceCharge = in_array($row->payment_type, ['Services', 'Management Fee'], true);
                $isTermAccrual = ((int) ($row->payment_term_id ?? 0)) > 0 || ((int) ($row->utility_term_id ?? 0)) > 0;

                // A non-cash journal tied to a term is a rent/utility accrual;
                // TransactionsTab derives those from the term due dates, so
                // migrating the journal too would double count - skip it. A
                // non-cash journal with no term link, however, is a manual ledger
                // charge or adjustment (e.g. a termination fee) that has no other
                // representation in the new system, so it is kept as a debit row.
                if ($mode === null && ! $isServiceCharge && $isTermAccrual) {
                    return null;
                }

                if ($mode !== null) {
                    $accountId = $this->accountId($mode['head']);
                    if (! $accountId) {
                        // A cash/bank head with no counterpart is a mapping gap to
                        // report, never something to quietly settle as cash.
                        $unmappedHeads[$mode['head']] = ($unmappedHeads[$mode['head']] ?? 0) + 1;

                        return null;
                    }
                    $counterAccountId = $this->accountId($mode['counter']) ?: $rentOut->account_id;
                } else {
                    $accountId = $rentOut->account_id;
                    $counterAccountId = $this->accountId($row->credit) ?: $rentOut->account_id;
                }

                $isMoneyIn = $mode !== null && $mode['direction'] === 'in';
                $amount = (float) ($row->amount ?? 0);
                $branchId = $row->branch_id ?: $rentOut->branch_id ?: 1;

                $transaction = [
                    'id' => $row->id,
                    'tenant_id' => $this->tenantId,
                    'branch_id' => $branchId,
                    'rent_out_id' => $row->rentout_id,
                    'date' => $row->date,
                    'due_date' => null,
                    'paid_date' => $mode !== null ? $row->date : null,
                    'cheque_date' => null,
                    'cheque_no' => null,
                    'bank_name' => null,
                    'credit' => $isMoneyIn ? $amount : 0,
                    'debit' => $isMoneyIn ? 0 : $amount,
                    'account_id' => $accountId,
                    'source' => 'Payment',
                    'source_id' => null,
                    'model' => null,
                    'model_id' => null,
                    'journal_id' => $row->id,
                    'journal_entry_id' => null,
                    'group' => $row->payment_type ?: 'Payment',
                    'category' => $row->category ?: null,
                    'payment_type' => $row->payment_type ?: 'Payment',
                    'remark' => $row->remark ?? null,
                    'reason' => $row->reason ?? null,
                    'voucher_no' => $row->voucher_no ?? null,
                    'created_by' => $this->userId($row->user_id ?? null),
                    'deleted_at' => null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ];

                $transaction = array_merge(
                    $transaction,
                    $this->transactionSourceFor($row, $rentOut, $mode, $termDueDates, $utilityDates, $oldCheques, $isServiceCharge, $isMoneyIn)
                );

                $journals[] = [
                    'id' => $row->id,
                    'tenant_id' => $this->tenantId,
                    'branch_id' => $branchId,
                    'date' => $row->date,
                    'description' => $transaction['group'],
                    'remarks' => $row->remark ?? '',
                    'reference_number' => $row->voucher_no ?? null,
                    'source' => $isMoneyIn ? 'income' : 'expense',
                    'model' => 'RentOut',
                    'model_id' => $row->rentout_id,
                    'created_by' => $transaction['created_by'],
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ];

                foreach ($this->entryPairFor($row, $accountId, $counterAccountId, $amount, $isMoneyIn, $branchId, $transaction) as $entry) {
                    $entries[] = $entry;
                }

                return $transaction;
            }
        );

        $this->writePropertyJournals($journals, $entries);

        if ($unmappedHeads) {
            $this->newLine();
            $this->error('Unmapped payment heads - these movements were NOT migrated:');
            foreach ($unmappedHeads as $head => $count) {
                $this->error("  account_heads.id {$head}: {$count} journal(s)");
            }
        }
    }

    /**
     * The cash/bank head the money actually moved through, plus the head on the
     * other side of the entry. Null when neither side is a payment head, which
     * means the row is an accrual or a charge rather than a movement.
     *
     * @return array{head:int,counter:int,direction:string}|null
     */
    private function paymentHeadFor(object $row): ?array
    {
        if (isset($this->paymentModeMap[$row->debit])) {
            return ['head' => (int) $row->debit, 'counter' => (int) $row->credit, 'direction' => 'in'];
        }

        if (isset($this->paymentModeMap[$row->credit])) {
            return ['head' => (int) $row->credit, 'counter' => (int) $row->debit, 'direction' => 'out'];
        }

        return null;
    }

    /**
     * Classify a movement against what it settles - a rent term, a utility term,
     * a service or a payout - and pick up the cheque reference where there is one.
     */
    private function transactionSourceFor(
        object $row,
        object $rentOut,
        ?array $mode,
        Collection $termDueDates,
        Collection $utilityDates,
        Collection $oldCheques,
        bool $isServiceCharge,
        bool $isMoneyIn
    ): array {
        if (($row->payment_term_id ?? 0) > 0) {
            $chequeId = is_numeric($row->rentout_cheque_id ?? null) ? (int) $row->rentout_cheque_id : 0;
            $cheque = $chequeId ? ($oldCheques[$chequeId] ?? null) : null;

            // A lease (sale) settles installments, not rent - mirror the label the
            // live app derives from agreement_type so ledgers read consistently.
            $agreementType = AgreementType::tryFrom($rentOut->agreement_type ?? 'rental');

            return [
                'source' => 'PaymentTerm',
                'source_id' => $row->payment_term_id,
                'model' => 'RentOutPaymentTerm',
                'model_id' => $row->payment_term_id,
                'group' => $agreementType?->config()->paymentGroupLabel ?? 'Rent Payment',
                // A term can be settled off the cheque schedule - by card or cash -
                // while still pointing at the cheque it replaced, so the head the
                // money moved through decides what this is called.
                'payment_type' => ($mode['head'] ?? null) !== null && $this->paymentModeMap[$mode['head']] === 'cheque' ? 'Cheque' : 'Rent',
                'due_date' => $this->normalizeDate($termDueDates[$row->payment_term_id] ?? null),
                'cheque_no' => $cheque->cheque_no ?? null,
                'bank_name' => $cheque->bank_name ?? null,
                'cheque_date' => $this->normalizeDate($cheque->date ?? null),
            ];
        }

        if (($row->utility_term_id ?? 0) > 0) {
            return [
                'source' => 'UtilityTerm',
                'source_id' => $row->utility_term_id,
                'model' => 'RentOutUtilityTerm',
                'model_id' => $row->utility_term_id,
                'group' => 'Utility Payment',
                'payment_type' => 'Utility',
                'due_date' => $this->normalizeDate($utilityDates[$row->utility_term_id] ?? null),
            ];
        }

        if ($isServiceCharge) {
            return [
                'source' => 'Service',
                'group' => $mode === null ? 'Service' : 'Service Payment',
                'payment_type' => 'Services',
            ];
        }

        if ($mode !== null && ! $isMoneyIn) {
            return ['source' => 'Payout', 'group' => 'Payout', 'payment_type' => 'Payout'];
        }

        return [];
    }

    /**
     * Receipt: Dr payment method, Cr counter account. Payout is the mirror.
     */
    private function entryPairFor(
        object $row,
        int $accountId,
        int $counterAccountId,
        float $amount,
        bool $isMoneyIn,
        int $branchId,
        array $transaction
    ): array {
        $base = [
            'tenant_id' => $this->tenantId,
            'journal_id' => $row->id,
            'branch_id' => $branchId,
            'date' => $row->date,
            'remarks' => $row->remark ?? null,
            'source' => $isMoneyIn ? 'income' : 'expense',
            'description' => $transaction['group'],
            'reference_number' => $row->voucher_no ?? null,
            'cheque_no' => $transaction['cheque_no'],
            'bank_name' => $transaction['bank_name'],
            'cheque_date' => $transaction['cheque_date'],
            'model' => 'RentOut',
            'model_id' => $row->rentout_id,
            'created_by' => $transaction['created_by'],
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ];

        [$debitAccount, $creditAccount] = $isMoneyIn
            ? [$accountId, $counterAccountId]
            : [$counterAccountId, $accountId];

        return [
            array_merge($base, [
                'account_id' => $debitAccount,
                'counter_account_id' => $creditAccount,
                'debit' => $amount,
                'credit' => 0,
            ]),
            array_merge($base, [
                'account_id' => $creditAccount,
                'counter_account_id' => $debitAccount,
                'debit' => 0,
                'credit' => $amount,
            ]),
        ];
    }

    /**
     * Write the journals and their entry pairs, then point each transaction at
     * the leg that represents it - the one sitting on its own payment account.
     */
    private function writePropertyJournals(array $journals, array $entries): void
    {
        if ($this->dryRun || ! $journals) {
            $this->info('Would post '.count($journals).' property journals with '.count($entries).' entries.');

            return;
        }

        $journalIds = array_column($journals, 'id');

        foreach (array_chunk($journalIds, 1000) as $chunk) {
            DB::table('journal_entries')->whereIn('journal_id', $chunk)->delete();
            DB::table('journals')->whereIn('id', $chunk)->delete();
        }

        foreach (array_chunk($journals, 500) as $chunk) {
            DB::table('journals')->insert($chunk);
        }

        foreach (array_chunk($entries, 500) as $chunk) {
            DB::table('journal_entries')->insert($chunk);
        }

        // The leg that represents this row is the one sitting on its own payment
        // account. Matching on the account alone rather than on which side is
        // positive keeps reversals - booked in the source as negative amounts -
        // linked up too.
        DB::statement('
            UPDATE rent_out_transactions t
            JOIN (
                SELECT journal_id, account_id, MIN(id) AS entry_id
                FROM journal_entries
                GROUP BY journal_id, account_id
            ) e ON e.journal_id = t.journal_id AND e.account_id = t.account_id
            SET t.journal_entry_id = e.entry_id
            WHERE t.journal_id IS NOT NULL
        ');

        $this->info('Posted '.count($journals).' property journals with '.count($entries).' entries.');
    }

    private function migrateRentOutSecurities(): void
    {
        $records = DB::connection('mysql2')->table('rentout_securities')->get();

        $this->migrateTable('rent out securities', $records, 'rent_out_securities', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchForRentout($row->rentout_id),
            'rent_out_id' => $row->rentout_id,
            'amount' => $row->security_amount ?? 0,
            'payment_mode' => $this->resolvePaymentMode($row->security_payment_mode_id ?? null),
            'status' => $this->securityStatusMap[$row->status ?? 'Pending'] ?? 'pending',
            'type' => strtolower($row->type ?? 'deposit') === 'guarantee' ? 'guarantee' : 'deposit',
            'due_date' => $row->due_date ?? null,
            'remarks' => null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migrateRentOutExtends(): void
    {
        $records = DB::connection('mysql2')->table('rentout_extends')->get();

        $this->migrateTable('rent out extends', $records, 'rent_out_extends', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchForRentout($row->rentout_id),
            'rent_out_id' => $row->rentout_id,
            'start_date' => $row->extended_from,
            'end_date' => $row->extended_to,
            'rent_amount' => $row->rent ?? 0,
            'payment_mode' => $this->resolvePaymentMode($row->payment_mode_id ?? null),
            'remarks' => null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migrateRentOutCheques(): void
    {
        $records = DB::connection('mysql2')->table('rentout_cheques')->get();

        $this->migrateTable('rent out cheques', $records, 'rent_out_cheques', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchForRentout($row->rentout_id),
            'rent_out_id' => $row->rentout_id,
            'cheque_no' => $row->cheque_no ?? '',
            'bank_name' => $row->bank_name ?? null,
            'amount' => $row->amount ?? 0,
            'date' => $row->date ?? null,
            'status' => $this->chequeStatusMap[$row->status ?? 1] ?? 'uncleared',
            'payee_name' => $row->payee_name ?? null,
            'remarks' => $row->remark ?? null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migrateUtilities(): void
    {
        $records = DB::connection('mysql2')->table('utilities')->get();

        $this->migrateTable('utilities', $records, 'utilities', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'name' => $row->name,
            'description' => $row->description ?? null,
            'created_by' => $row->created_by ?? null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migrateRentOutUtilityTerms(): void
    {
        $records = DB::connection('mysql2')->table('rentout_utility_terms')->get();

        $this->migrateTable('rent out utility terms', $records, 'rent_out_utility_terms', function ($row) {
            $paid = ($row->amount ?? 0) - ($row->balance ?? 0);

            return [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $this->branchForRentout($row->rentout_id),
                'rent_out_id' => $row->rentout_id,
                'utility_id' => $row->utility_id,
                'amount' => $row->amount ?? 0,
                'balance' => $row->balance ?? 0,
                'paid' => max($paid, 0),
                'payment_mode' => null,
                'paid_date' => null,
                'date' => $row->date ?? null,
                'remarks' => null,
                'deleted_at' => $row->deleted_at ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        });
    }

    /**
     * Service charges. The old project has no service charge table - `rentout_services`
     * there is only a lookup of names. The charges themselves live as journals tagged
     * 'Service Charge' / 'Services', with the billing basis (period, unit size, rate)
     * kept as JSON in `more_details`. That JSON is exactly what the new
     * rent_out_services table models, so the charges are rebuilt from those journals.
     */
    private function migrateRentOutServices(): void
    {
        $rentOutIds = DB::table('rent_outs')->pluck('id')->flip();

        $records = DB::connection('mysql2')
            ->table('journals')
            ->where('category', 'Service Charge')
            ->where('payment_type', 'Services')
            ->whereNotNull('more_details')
            ->whereNull('deleted_at')
            ->where('rentout_id', '>', 0)
            ->orderBy('id')
            ->get();

        $this->migrateTable('rent out services', $records, 'rent_out_services', function ($row) use ($rentOutIds) {
            if (! isset($rentOutIds[$row->rentout_id])) {
                return; // journal points at an agreement that no longer exists
            }

            $details = json_decode($row->more_details ?? '', true) ?: [];
            $unitSize = $this->numericOrNull($details['unit_size'] ?? null);
            $rate = $this->numericOrNull($details['per_square_meter_price'] ?? null);

            return [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $row->branch_id ?: $this->branchForRentout($row->rentout_id),
                'rent_out_id' => $row->rentout_id,
                'name' => 'Service Charge',
                'amount' => $row->amount ?? 0,
                'description' => null,
                'start_date' => $this->normalizeDate($details['start_date'] ?? null),
                'end_date' => $this->normalizeDate($details['end_date'] ?? null),
                'no_of_days' => $this->numericOrNull($details['no_of_days'] ?? null),
                'no_of_months' => $this->numericOrNull($details['no_of_months'] ?? null),
                'unit_size' => $unitSize,
                'per_square_meter_price' => $rate,
                // The old report derived this on the fly; the new table stores it.
                'per_day_price' => ($unitSize && $rate) ? round($unitSize * $rate * 12 / 365, 2) : null,
                'reason' => $row->reason ?? null,
                'remark' => $row->remark ?? null,
                'created_by' => $row->user_id ?: null,
                'deleted_at' => null,
                'created_at' => $row->created_at ?? $row->date ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        });
    }

    private function numericOrNull($value): int|float|null
    {
        return is_numeric($value) ? $value + 0 : null;
    }

    private function migrateRentOutNotes(): void
    {
        $records = DB::connection('mysql2')->table('rentout_notes')->get();

        $this->migrateTable('rent out notes', $records, 'rent_out_notes', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'rent_out_id' => $row->rentout_id,
            'note' => $row->notes ?? '',
            'created_by' => null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migrateRentOutPaymentTerms(): void
    {
        // Old table is `payment_terms`, new table is `rent_out_payment_terms`
        // Only migrate records that have a rentout_id
        $records = DB::connection('mysql2')
            ->table('payment_terms')
            ->whereNotNull('rentout_id')
            ->where('rentout_id', '>', 0)
            ->get();

        $this->migrateTable('rent out payment terms', $records, 'rent_out_payment_terms', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchForRentout($row->rentout_id),
            'rent_out_id' => $row->rentout_id,
            'amount' => $row->rent ?? 0,
            'discount' => $row->discount ?? 0,
            'total' => $row->amount ?? 0,
            'paid' => $row->paid ?? 0,
            'balance' => max(($row->amount ?? 0) - ($row->paid ?? 0), 0),
            'due_date' => $row->date,
            'paid_date' => null,
            'status' => ($row->paid ?? 0) >= ($row->amount ?? 0) && ($row->amount ?? 0) > 0 ? 'paid' : 'pending',
            'remarks' => $row->remark ?? null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migrateDocumentTypes(): void
    {
        $records = DB::connection('mysql2')->table('document_types')->get();

        $this->migrateTable('document types', $records, 'document_types', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'name' => $row->name,
            'arabic_name' => null,
            'description' => null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migrateRentOutDocuments(): void
    {
        $records = DB::connection('mysql2')
            ->table('account_head_documents')
            ->where('model', 'like', '%Rentout%')
            ->whereNotNull('model_id')
            ->where('model_id', '>', 0)
            ->get();

        $this->migrateTable('rent out documents', $records, 'rent_out_documents', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchForRentout($row->model_id),
            'rent_out_id' => $row->model_id,
            'document_type_id' => $row->document_type_id,
            'name' => $row->name,
            'path' => $row->path,
            'remarks' => $row->remarks ?? null,
            'created_by' => null,
            'deleted_at' => $row->deleted_at ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migrateTenantDetails(): void
    {
        $records = DB::connection('mysql2')->table('tenant_details')->get();

        $this->migrateTable('tenant details', $records, 'tenant_details', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'branch_id' => $row->branch_id ?? 1,
            'property_id' => $row->property_id,
            'name' => $row->customer_name ?? '',
            'mobile' => $row->mobile ?? null,
            'email' => $row->email ?? null,
            'emirates_id' => null,
            'passport_no' => null,
            'nationality' => null,
            'address' => null,
            'deleted_at' => null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    private function migratePropertyLeads(): void
    {
        if (! $this->tableExists('property_leads')) {
            $this->warn('Source property_leads table not found - skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('property_leads')->orderBy('id')->get();

        $this->migrateTable('property leads', $records, 'property_leads', function ($row) {
            $remarks = $row->remarks ?? null;
            if (is_string($remarks) && $remarks !== '') {
                $decoded = json_decode($remarks, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $remarks = json_encode($decoded);
                }
            } elseif (! is_string($remarks)) {
                $remarks = null;
            }

            $meetingTime = $row->meeting_time ?? null;
            if ($meetingTime === '00:00:00') {
                $meetingTime = null;
            }

            return [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $row->branch_id ?? 1,
                'name' => $row->name ?? '',
                'mobile' => $row->mobile ?? null,
                'email' => $row->email ?? null,
                'company_name' => $row->company_name ?? null,
                'company_contact_no' => $row->company_contact_no ?? null,
                'source' => $row->source ?? null,
                'type' => $row->type ?? 'Sales',
                'property_group_id' => $row->property_group_id ?? null,
                'assigned_to' => $row->assigned_to ?? null,
                'assign_date' => $this->normalizeDate($row->assign_date ?? null),
                'country_id' => $row->country_id ?? null,
                'nationality' => $this->normalizeNationality($row->nationality ?? null),
                'location' => $row->location ?? null,
                'meeting_date' => $this->normalizeDate($row->meeting_date ?? null),
                'meeting_time' => $meetingTime,
                'remarks' => $remarks,
                'status' => $row->status ?? 'New Lead',
                'created_by' => $row->assigned_to ?? null,
                'updated_by' => $row->assigned_to ?? null,
                'deleted_at' => $row->deleted_at ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        });
    }

    private function migratePropertyAssets(): void
    {
        $this->info('Migrating property_assets -> products...');

        if (! $this->tableExists('property_assets')) {
            $this->warn('Source table property_assets does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')
            ->table('property_assets')
            ->leftJoin('brands', 'property_assets.brand_id', '=', 'brands.id')
            ->leftJoin('units', 'property_assets.unit_id', '=', 'units.id')
            ->leftJoin('asset_groups', 'property_assets.asset_group_id', '=', 'asset_groups.id')
            ->orderBy('property_assets.id')
            ->get([
                'property_assets.*',
                'brands.name as brand_name',
                'units.name as unit_name',
                'asset_groups.name as group_name',
            ]);

        if ($records->isEmpty()) {
            $this->warn('No property assets found. Skipping.');

            return;
        }

        $branches = Branch::all();
        $bar = $this->output->createProgressBar($records->count());
        $skipped = 0;
        $updated = 0;
        $created = 0;

        foreach ($records as $row) {
            try {
                $assetData = [
                    'tenant_id' => $this->tenantId,
                    'type' => 'product',
                    'second_reference_no' => $row->id,
                    'name' => $row->name,
                    'name_arabic' => $row->name_arabic ?? null,
                    'color' => $row->color ?? null,
                    'part_no' => $row->item_no ?? null,
                    'barcode_number' => $row->barcode ?? null,
                    'cost' => $row->cost ?? 0,
                    'mrp' => $row->price ?? 0,
                    'location' => $row->location ?? null,
                    'description' => $row->remarks ?? null,
                    'thumbnail' => $row->image_path ?? null,
                    'is_selling' => false,
                    'unit' => $row->unit_name ?? 'Nos',
                    'brand_id' => $row->brand_name ?? null,
                    'department' => 'Asset',
                    'main_category' => $row->group_name ?? 'General',
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ];

                if (! $this->dryRun) {
                    $data = Product::constructData($assetData, 1);
                    unset($data['department'], $data['unit'], $data['main_category'], $data['sub_category']);

                    $existing = Product::where('type', 'product')
                        ->where('second_reference_no', $row->id)
                        ->first();

                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                    } else {
                        $data['deleted_at'] = $row->deleted_at ?? null;
                        $product = Product::create($data);

                        foreach ($branches as $branch) {
                            BranchProductCreationJob::dispatch($branch->id, $this->tenantId, $product->id);
                        }

                        $created++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error migrating asset id={$row->id} ({$row->name}): {$e->getMessage()}");
                Log::error('MigratePropertyData: error on property_asset id='.$row->id, ['error' => $e->getMessage()]);
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Assets: {$created} created, {$updated} updated.".($skipped ? " {$skipped} failed (check logs)." : ''));
    }

    private function migrateSupplyRequests(): void
    {
        if (! $this->tableExists('property_asset_supplies')) {
            $this->warn('Source table property_asset_supplies does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('property_asset_supplies')->orderBy('id')->get();

        $this->migrateTable('supply requests', $records, 'supply_requests', function ($row) {
            $createdBy = $this->userId($row->created_by);

            if (! $createdBy) {
                return;
            }

            $updatedBy = $this->userId($row->updated_by);
            $approvedBy = $this->userId($row->approved_by);
            $accountedBy = $this->userId($row->accounted_by);
            $finalApprovedBy = $this->userId($row->final_approved_by);
            $completedBy = $this->userId($row->completed_by);

            $meta = $row->property_id ? $this->propertyMeta($row->property_id) : null;

            return [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $row->branch_id ?? 1,
                'date' => $row->date,
                'order_no' => $row->order_no ?? null,
                'contact_person' => $row->contact_person ?? null,
                'property_id' => $row->property_id ?? null,
                'property_group_id' => $meta->property_group_id ?? null,
                'property_building_id' => $meta->property_building_id ?? null,
                'property_type_id' => $meta->property_type_id ?? null,
                'type' => $row->type ?? 'Add',
                'total' => $row->total ?? 0,
                'other_charges' => $row->other_charges ?? 0,
                'grand_total' => $row->grand_total ?? 0,
                'payment_mode_id' => $this->accountId($row->payment_mode_id),
                'remarks' => $row->remarks ?? null,
                'status' => $this->assetSupplyStatusMap[$row->status] ?? 'requirement',
                'approved_by' => $approvedBy,
                'approved_at' => $approvedBy ? $row->updated_at : null,
                'accounted_by' => $accountedBy,
                'accounted_at' => $accountedBy ? $row->updated_at : null,
                'final_approved_by' => $finalApprovedBy,
                'final_approved_at' => $finalApprovedBy ? $row->updated_at : null,
                'completed_by' => $completedBy,
                'completed_at' => $completedBy ? $row->updated_at : null,
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
                'deleted_at' => null,
            ];
        });
    }

    private function migrateSupplyRequestItems(): void
    {
        if (! $this->tableExists('property_asset_supply_items')) {
            $this->warn('Source table property_asset_supply_items does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('property_asset_supply_items')->orderBy('id')->get();

        $this->migrateTable('supply request items', $records, 'supply_request_items', function ($row) {
            if (! $this->supplyRequestExists($row->property_asset_supply_id)) {
                return;
            }

            $productId = $this->productId($row->property_asset_id);

            if (! $productId) {
                Log::warning('MigratePropertyData: product not found for property_asset_id: '.$row->property_asset_id.', skipping item id: '.$row->id);

                return;
            }

            return [
                'id' => $row->id,
                'supply_request_id' => $row->property_asset_supply_id,
                'branch_id' => $row->store_id ?? null,
                'product_id' => $productId,
                'mode' => $row->mode ?? 'New',
                'quantity' => $row->quantity ?? 1,
                'unit_price' => $row->unit_price ?? 0,
                'remarks' => $row->remarks ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        });
    }

    private function migrateSupplyRequestNotes(): void
    {
        if (! $this->tableExists('property_asset_supply_notes')) {
            $this->warn('Source table property_asset_supply_notes does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('property_asset_supply_notes')->orderBy('id')->get();

        $this->migrateTable('supply request notes', $records, 'supply_request_notes', function ($row) {
            if (! $this->supplyRequestExists($row->property_asset_supply_id)) {
                return;
            }

            return [
                'id' => $row->id,
                'supply_request_id' => $row->property_asset_supply_id,
                'note' => $row->note,
                'created_by' => $this->userId($row->created_by) ?? 1,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        });
    }

    private function migrateSupplyRequestImages(): void
    {
        if (! $this->tableExists('property_asset_supply_images')) {
            $this->warn('Source table property_asset_supply_images does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('property_asset_supply_images')->orderBy('id')->get();

        $this->migrateTable('supply request images', $records, 'supply_request_images', function ($row) {
            if (! $this->supplyRequestExists($row->asset_supply_id)) {
                return;
            }

            return [
                'id' => $row->id,
                'supply_request_id' => $row->asset_supply_id,
                'name' => $row->name,
                'path' => $row->path,
                'type' => $row->type ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        });
    }

    private function migrateComplaintCategories(): void
    {
        if (! $this->tableExists('complaint_categories')) {
            $this->warn('Source table complaint_categories does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('complaint_categories')->get();

        $this->migrateTable('complaint categories', $records, 'complaint_categories', fn ($row) => [
            'id' => $row->id,
            'tenant_id' => $this->tenantId,
            'branch_id' => $row->branch_id ?? 1,
            'name' => $row->name,
            'arabic_name' => $row->arabic_name ?? null,
            'description' => $row->description ?? null,
            'is_active' => true,
            'created_by' => $row->created_by ?? 1,
            'updated_by' => $row->updated_by ?? null,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
            'deleted_at' => $row->deleted_at ?? null,
        ]);
    }

    private function migrateComplaints(): void
    {
        if (! $this->tableExists('complaints')) {
            $this->warn('Source table complaints does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('complaints')->get();
        $defaultCategoryId = DB::connection('mysql2')->table('complaint_categories')->value('id');

        $this->migrateTable('complaints', $records, 'complaints', function ($row) use ($defaultCategoryId) {
            $categoryId = $row->complaint_category_id ?? $row->category_id ?? $defaultCategoryId;
            if (! $categoryId) {
                return;
            }

            return [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $row->branch_id ?? 1,
                'complaint_category_id' => $categoryId,
                'name' => $row->name,
                'arabic_name' => $row->arabic_name ?? null,
                'description' => $row->description ?? null,
                'is_active' => true,
                'created_by' => $row->created_by ?? 1,
                'updated_by' => $row->updated_by ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
                'deleted_at' => $row->deleted_at ?? null,
            ];
        });
    }

    private function migrateMaintenances(): void
    {
        if (! $this->tableExists('maintenances')) {
            $this->warn('Source table maintenances does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('maintenances')->get();

        $this->migrateTable('maintenances', $records, 'maintenances', function ($row) {
            $meta = $this->propertyMeta($row->property_id);
            if (! $meta) {
                return;
            }

            $segment = null;
            if (! empty($row->segment)) {
                $segment = $this->segmentMap[$row->segment] ?? strtolower($row->segment);
            }

            return [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $row->branch_id ?? 1,
                'property_id' => $row->property_id,
                'property_group_id' => $meta->property_group_id ?? null,
                'property_building_id' => $meta->property_building_id ?? null,
                'property_type_id' => $meta->property_type_id ?? null,
                'rent_out_id' => $row->rentout_id ?? null,
                'account_id' => $this->accountId($row->customer_id ?? null),
                'date' => $row->date ?? null,
                'time' => $row->time ?? null,
                'priority' => $this->priorityMap[$row->priority ?? 'Low'] ?? 'low',
                'segment' => $segment,
                'contact_no' => $row->contact_no ?? null,
                'remark' => $row->remark ?? null,
                'company_remark' => $row->company_remark ?? null,
                'status' => $this->maintenanceStatusMap[$row->status ?? 'pending'] ?? 'pending',
                'created_by' => $row->created_by ?? 1,
                'completed_by' => $row->completed_by ?? null,
                'completed_at' => $row->completed_at ?? null,
                'updated_by' => $row->updated_by ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
                'deleted_at' => $row->deleted_at ?? null,
            ];
        });
    }

    private function migrateMaintenanceComplaints(): void
    {
        if (! $this->tableExists('maintenance_complaints')) {
            $this->warn('Source table maintenance_complaints does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('maintenance_complaints')->get();

        $this->migrateTable('maintenance complaints', $records, 'maintenance_complaints', function ($row) {
            $branchMap = $this->maintenanceBranchMap();
            if (! array_key_exists($row->maintenance_id, $branchMap)) {
                return;
            }

            return [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $row->branch_id ?? $branchMap[$row->maintenance_id] ?? 1,
                'maintenance_id' => $row->maintenance_id,
                'complaint_id' => $row->complaint_id ?? null,
                'status' => $this->maintenanceComplaintStatusMap[$row->status ?? 'pending'] ?? 'pending',
                'technician_id' => $row->technician_id ?? null,
                'technician_remark' => $row->technician_remark ?? null,
                'assigned_by' => $row->assigned_by ?? null,
                'assigned_at' => $row->assigned_at ?? null,
                'completed_by' => $row->completed_by ?? null,
                'completed_at' => $row->completed_at ?? null,
                'created_by' => $row->created_by ?? 1,
                'updated_by' => $row->updated_by ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
                'deleted_at' => $row->deleted_at ?? null,
            ];
        });
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::connection('mysql2')->getSchemaBuilder()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function normalizeDate(?string $value): ?string
    {
        if (! $value || $value === '0000-00-00' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        return $value;
    }
}
