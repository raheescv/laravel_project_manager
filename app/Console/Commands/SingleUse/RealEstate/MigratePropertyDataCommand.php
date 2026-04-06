<?php

namespace App\Console\Commands\SingleUse\RealEstate;

use App\Jobs\BranchProductCreationJob;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Property;
use App\Models\User;
use Illuminate\Console\Command;
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
            $this->migrateUsers();
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
                $this->paymentModeMap[$mode->id] = 'cash'; // default fallback
            }
        }

        $this->info('Payment mode map built: '.count($this->paymentModeMap).' modes mapped.');
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
        $this->info('Migrating users...');
        $users = DB::connection('mysql2')->table('users')->get();
        $bar = $this->output->createProgressBar($users->count());

        foreach ($users as $row) {
            $data = [
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
            ];

            if (! $this->dryRun) {
                DB::table('users')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$users->count()} users.");

        // Migrate employees from old employees table as type='employee'
        $this->info('Migrating employees -> users (type=employee)...');
        $employees = DB::connection('mysql2')->table('employees')->get();
        $bar = $this->output->createProgressBar($employees->count());

        foreach ($employees as $row) {
            $data = [
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
            ];

            if (! $this->dryRun) {
                DB::table('users')->updateOrInsert(
                    ['tenant_id' => $this->tenantId, 'email' => $data['email']],
                    $data
                );
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$employees->count()} employees as users (type=employee).");
    }

    private function migrateAccountHeads(): void
    {
        $this->info('Migrating account_heads -> accounts...');

        // Migrate payment mode accounts (asset accounts like Cash, Bank, etc.)
        $accountHeads = DB::connection('mysql2')
            ->table('account_heads')
            ->whereIn('account_category_id', [16, 17])
            ->get();
        $bar = $this->output->createProgressBar($accountHeads->count());

        foreach ($accountHeads as $row) {
            $name = ucfirst(strtolower($row->name));

            $data = [
                'tenant_id' => $this->tenantId,
                'account_type' => 'asset',
                'name' => $name,
                'slug' => Str::slug($name),
                'second_reference_no' => $row->id,
            ];

            if (! $this->dryRun) {
                Account::updateOrCreate(
                    ['tenant_id' => $this->tenantId, 'account_type' => 'asset', 'name' => $name],
                    $data
                );
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$accountHeads->count()} payment mode account heads.");
    }

    private function migrateCustomers(): void
    {
        $this->info('Migrating customers -> accounts...');
        $customers = DB::connection('mysql2')
            ->table('customers')
            ->join('account_heads', 'customers.account_head_id', '=', 'account_heads.id')
            ->where('account_heads.id', '!=', 2)
            ->select('customers.*', 'customers.account_head_id', 'account_heads.name as name')
            ->get();
        $bar = $this->output->createProgressBar($customers->count());

        foreach ($customers as $row) {
            $name = explode('@', $row->name);
            $nationality = $this->normalizeNationality($row->nationality ?? null);

            $data = [
                'tenant_id' => $this->tenantId,
                'account_type' => 'asset',
                'second_reference_no' => $row->account_head_id,
                'model' => 'customer',
                'name' => ucfirst(strtolower($name[0])),
                'email' => $row->email ?? null,
                'mobile' => $row->mobile ?? null,
                'whatsapp_mobile' => $row->whatsapp_no ?? null,
                'nationality' => $nationality,
                'dob' => ($row->dob ?? null) !== '0000-00-00' ? ($row->dob ?? null) : null,
                'id_no' => $row->id_no ?? null,
                'company' => $row->company ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('accounts')->insertOrIgnore($data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$customers->count()} customers.");
    }

    private function migrateVendors(): void
    {
        $this->info('Migrating vendors -> accounts...');
        $vendors = DB::connection('mysql2')
            ->table('vendors')
            ->join('account_heads', 'vendors.account_head_id', '=', 'account_heads.id')
            ->select('vendors.*', 'vendors.account_head_id', 'account_heads.name as name')
            ->get();
        $bar = $this->output->createProgressBar($vendors->count());

        foreach ($vendors as $row) {
            $data = [
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
            ];

            if (! $this->dryRun) {
                DB::table('accounts')->insertOrIgnore($data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$vendors->count()} vendors.");
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
        $this->info('Migrating property_groups...');
        $records = DB::connection('mysql2')->table('property_groups')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $data = [
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
            ];

            if (! $this->dryRun) {
                DB::table('property_groups')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} property groups.");
    }

    private function migratePropertyBuildings(): void
    {
        $this->info('Migrating property_buildings...');
        $records = DB::connection('mysql2')->table('property_buildings')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $data = [
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
                'account_id' => $row->account_head_id
                    ? Account::where('second_reference_no', $row->account_head_id)->value('id')
                    : null,
                'remark' => $row->remark ?? null,
                'deleted_at' => $row->deleted_at ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('property_buildings')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} property buildings.");
    }

    private function migratePropertyTypes(): void
    {
        $this->info('Migrating property_types...');
        $records = DB::connection('mysql2')->table('property_types')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'name' => $row->name,
                'description' => null,
                'deleted_at' => $row->deleted_at ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (! $this->dryRun) {
                DB::table('property_types')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} property types.");
    }

    private function migrateProperties(): void
    {
        $this->info('Migrating properties...');
        $records = DB::connection('mysql2')->table('properties')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            // Resolve group_id from building
            $groupId = DB::connection('mysql2')
                ->table('property_buildings')
                ->where('id', $row->property_building_id)
                ->value('property_group_id') ?? 0;

            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $row->branch_id ?? 1,
                'property_group_id' => $groupId,
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
            ];

            if (! $this->dryRun) {
                DB::table('properties')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} properties.");
    }

    private function migrateRentOuts(): void
    {
        $this->info('Migrating rentouts -> rent_outs...');
        $records = DB::connection('mysql2')->table('rentouts')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $row->branch_id ?? 1,
                'property_id' => $row->property_id,
                'property_building_id' => $row->property_building_id ?? 0,
                'property_type_id' => $row->property_type_id ?? null,
                'property_group_id' => $row->property_group_id ?? 0,
                'account_id' => $row->customer_id
                    ? Account::where('second_reference_no', $row->customer_id)->value('id')
                    : null,
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
                'management_fee_payment_method_id' => ($row->management_fee_payment_mode_id ?? null)
                    ? Account::where('second_reference_no', $row->management_fee_payment_mode_id)->where('tenant_id', $this->tenantId)->value('id')
                    : null,
                'management_fee_remarks' => $row->management_fee_remarks ?? null,
                'down_payment' => $row->down_payment ?? 0,
                'down_payment_payment_method_id' => ($row->down_payment_mode_id ?? null)
                    ? Account::where('second_reference_no', $row->down_payment_mode_id)->where('tenant_id', $this->tenantId)->value('id')
                    : null,
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
            ];

            if (! $this->dryRun) {
                DB::table('rent_outs')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} rent outs.");
    }

    private function migrateRentOutSecurities(): void
    {
        $this->info('Migrating rentout_securities -> rent_out_securities...');
        $records = DB::connection('mysql2')->table('rentout_securities')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $branchId = DB::connection('mysql2')
                ->table('rentouts')
                ->where('id', $row->rentout_id)
                ->value('branch_id') ?? 1;

            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $branchId,
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
            ];

            if (! $this->dryRun) {
                DB::table('rent_out_securities')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} rent out securities.");
    }

    private function migrateRentOutExtends(): void
    {
        $this->info('Migrating rentout_extends -> rent_out_extends...');
        $records = DB::connection('mysql2')->table('rentout_extends')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $branchId = DB::connection('mysql2')
                ->table('rentouts')
                ->where('id', $row->rentout_id)
                ->value('branch_id') ?? 1;

            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $branchId,
                'rent_out_id' => $row->rentout_id,
                'start_date' => $row->extended_from,
                'end_date' => $row->extended_to,
                'rent_amount' => $row->rent ?? 0,
                'payment_mode' => $this->resolvePaymentMode($row->payment_mode_id ?? null),
                'remarks' => null,
                'deleted_at' => $row->deleted_at ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('rent_out_extends')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} rent out extends.");
    }

    private function migrateRentOutCheques(): void
    {
        $this->info('Migrating rentout_cheques -> rent_out_cheques...');
        $records = DB::connection('mysql2')->table('rentout_cheques')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $branchId = DB::connection('mysql2')
                ->table('rentouts')
                ->where('id', $row->rentout_id)
                ->value('branch_id') ?? 1;

            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $branchId,
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
            ];

            if (! $this->dryRun) {
                DB::table('rent_out_cheques')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} rent out cheques.");
    }

    private function migrateUtilities(): void
    {
        $this->info('Migrating utilities -> utilities...');
        $records = DB::connection('mysql2')->table('utilities')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'name' => $row->name,
                'description' => $row->description ?? null,
                'created_by' => $row->created_by ?? null,
                'deleted_at' => $row->deleted_at ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('utilities')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} utilities.");
    }

    private function migrateRentOutUtilityTerms(): void
    {
        $this->info('Migrating rentout_utility_terms -> rent_out_utility_terms...');
        $records = DB::connection('mysql2')->table('rentout_utility_terms')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $branchId = DB::connection('mysql2')
                ->table('rentouts')
                ->where('id', $row->rentout_id)
                ->value('branch_id') ?? 1;

            $paid = ($row->amount ?? 0) - ($row->balance ?? 0);

            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $branchId,
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

            if (! $this->dryRun) {
                DB::table('rent_out_utility_terms')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} rent out utility terms.");
    }

    private function migrateRentOutServices(): void
    {
        // In old project, rentout_services is just a lookup table (id, name) with no rent_out FK.
        // In new project, rent_out_services requires a rent_out_id FK.
        // These schemas are incompatible — skipping to avoid FK violations.
        $records = DB::connection('mysql2')->table('rentout_services')->get();
        $this->warn("Skipped {$records->count()} rentout_services (old table is a lookup with no rent_out FK, incompatible with new schema).");
    }

    private function migrateRentOutNotes(): void
    {
        $this->info('Migrating rentout_notes -> rent_out_notes...');
        $records = DB::connection('mysql2')->table('rentout_notes')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'rent_out_id' => $row->rentout_id,
                'note' => $row->notes ?? '',
                'created_by' => null,
                'deleted_at' => $row->deleted_at ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('rent_out_notes')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} rent out notes.");
    }

    private function migrateRentOutPaymentTerms(): void
    {
        $this->info('Migrating payment_terms -> rent_out_payment_terms...');
        // Old table is `payment_terms`, new table is `rent_out_payment_terms`
        // Only migrate records that have a rentout_id
        $records = DB::connection('mysql2')
            ->table('payment_terms')
            ->whereNotNull('rentout_id')
            ->where('rentout_id', '>', 0)
            ->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $branchId = DB::connection('mysql2')
                ->table('rentouts')
                ->where('id', $row->rentout_id)
                ->value('branch_id') ?? 1;

            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $branchId,
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
            ];

            if (! $this->dryRun) {
                DB::table('rent_out_payment_terms')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} rent out payment terms.");
    }

    private function migrateDocumentTypes(): void
    {
        $this->info('Migrating document_types...');
        $records = DB::connection('mysql2')->table('document_types')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'name' => $row->name,
                'arabic_name' => null,
                'description' => null,
                'deleted_at' => $row->deleted_at ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('document_types')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} document types.");
    }

    private function migrateRentOutDocuments(): void
    {
        $this->info('Migrating account_head_documents (Rentout) -> rent_out_documents...');
        $records = DB::connection('mysql2')
            ->table('account_head_documents')
            ->where('model', 'like', '%Rentout%')
            ->whereNotNull('model_id')
            ->where('model_id', '>', 0)
            ->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $branchId = DB::connection('mysql2')
                ->table('rentouts')
                ->where('id', $row->model_id)
                ->value('branch_id') ?? 1;

            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $branchId,
                'rent_out_id' => $row->model_id,
                'document_type_id' => $row->document_type_id,
                'name' => $row->name,
                'path' => $row->path,
                'remarks' => $row->remarks ?? null,
                'created_by' => null,
                'deleted_at' => $row->deleted_at ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('rent_out_documents')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} rent out documents.");
    }

    private function migrateTenantDetails(): void
    {
        $this->info('Migrating tenant_details...');
        $records = DB::connection('mysql2')->table('tenant_details')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $data = [
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
            ];

            if (! $this->dryRun) {
                DB::table('tenant_details')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} tenant details.");
    }

    private function migratePropertyLeads(): void
    {
        $this->info('Migrating property_leads...');

        if (! DB::connection('mysql2')->getSchemaBuilder()->hasTable('property_leads')) {
            $this->warn('Source property_leads table not found - skipping.');

            return;
        }

        $records = DB::connection('mysql2')
            ->table('property_leads')
            ->orderBy('id')
            ->get();

        if ($records->isEmpty()) {
            $this->warn('No property_leads records to migrate.');

            return;
        }

        $bar = $this->output->createProgressBar($records->count());
        $migrated = 0;

        foreach ($records as $row) {
            $remarks = $row->remarks ?? null;
            if (is_string($remarks) && $remarks !== '') {
                $decoded = json_decode($remarks, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $remarks = json_encode($decoded);
                }
            } elseif (! is_string($remarks)) {
                $remarks = null;
            }

            $meetingDate = $this->normalizeDate($row->meeting_date ?? null);
            $meetingTime = $row->meeting_time ?? null;
            if ($meetingTime === '00:00:00') {
                $meetingTime = null;
            }

            $data = [
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
                'meeting_date' => $meetingDate,
                'meeting_time' => $meetingTime,
                'remarks' => $remarks,
                'status' => $row->status ?? 'New Lead',
                'created_by' => $row->assigned_to ?? null,
                'updated_by' => $row->assigned_to ?? null,
                'deleted_at' => $row->deleted_at ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('property_leads')->updateOrInsert(['id' => $row->id], $data);
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$migrated} property leads.");
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
        $this->info('Migrating supply requests (property_asset_supplies)...');

        if (! $this->tableExists('property_asset_supplies')) {
            $this->warn('Source table property_asset_supplies does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('property_asset_supplies')->orderBy('id')->get();

        if ($records->isEmpty()) {
            $this->warn('No supply requests found. Skipping.');

            return;
        }

        $bar = $this->output->createProgressBar($records->count());
        $skipped = 0;

        foreach ($records as $row) {
            $createdBy = User::where('type', 'user')->where('second_reference_no', $row->created_by)->value('id');

            if (! $createdBy) {
                $skipped++;
                $bar->advance();

                continue;
            }

            $updatedBy = $row->updated_by
                ? User::where('type', 'user')->where('second_reference_no', $row->updated_by)->value('id')
                : null;

            $approvedBy = $row->approved_by
                ? User::where('type', 'user')->where('second_reference_no', $row->approved_by)->value('id')
                : null;

            $accountedBy = $row->accounted_by
                ? User::where('type', 'user')->where('second_reference_no', $row->accounted_by)->value('id')
                : null;

            $finalApprovedBy = $row->final_approved_by
                ? User::where('type', 'user')->where('second_reference_no', $row->final_approved_by)->value('id')
                : null;

            $completedBy = $row->completed_by
                ? User::where('type', 'user')->where('second_reference_no', $row->completed_by)->value('id')
                : null;

            $paymentModeId = $row->payment_mode_id
                ? Account::where('second_reference_no', $row->payment_mode_id)->value('id')
                : null;

            $propertyGroupId = null;
            $propertyBuildingId = null;
            $propertyTypeId = null;

            if ($row->property_id) {
                $property = Property::find($row->property_id);
                if ($property) {
                    $propertyGroupId = $property->property_group_id;
                    $propertyBuildingId = $property->property_building_id;
                    $propertyTypeId = $property->property_type_id;
                }
            }

            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $row->branch_id ?? 1,
                'date' => $row->date,
                'order_no' => $row->order_no ?? null,
                'contact_person' => $row->contact_person ?? null,
                'property_id' => $row->property_id ?? null,
                'property_group_id' => $propertyGroupId,
                'property_building_id' => $propertyBuildingId,
                'property_type_id' => $propertyTypeId,
                'type' => $row->type ?? 'Add',
                'total' => $row->total ?? 0,
                'other_charges' => $row->other_charges ?? 0,
                'grand_total' => $row->grand_total ?? 0,
                'payment_mode_id' => $paymentModeId,
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

            if (! $this->dryRun) {
                DB::table('supply_requests')->updateOrInsert(['id' => $row->id], $data);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} supply requests.".($skipped ? " Skipped {$skipped} (missing created_by user)." : ''));
    }

    private function migrateSupplyRequestItems(): void
    {
        $this->info('Migrating supply request items (property_asset_supply_items)...');

        if (! $this->tableExists('property_asset_supply_items')) {
            $this->warn('Source table property_asset_supply_items does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('property_asset_supply_items')->orderBy('id')->get();

        if ($records->isEmpty()) {
            $this->warn('No supply request items found. Skipping.');

            return;
        }

        $bar = $this->output->createProgressBar($records->count());
        $skipped = 0;

        foreach ($records as $row) {
            $supplyRequestExists = DB::table('supply_requests')->where('id', $row->property_asset_supply_id)->exists();
            if (! $supplyRequestExists) {
                $skipped++;
                $bar->advance();

                continue;
            }

            $productId = Product::where('type', 'product')->where('second_reference_no', $row->property_asset_id)->value('id');

            if (! $productId) {
                Log::warning('MigratePropertyData: product not found for property_asset_id: '.$row->property_asset_id.', skipping item id: '.$row->id);
                $skipped++;
                $bar->advance();

                continue;
            }

            $data = [
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

            if (! $this->dryRun) {
                DB::table('supply_request_items')->updateOrInsert(['id' => $row->id], $data);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} supply request items.".($skipped ? " Skipped {$skipped} (missing parent or product)." : ''));
    }

    private function migrateSupplyRequestNotes(): void
    {
        $this->info('Migrating supply request notes (property_asset_supply_notes)...');

        if (! $this->tableExists('property_asset_supply_notes')) {
            $this->warn('Source table property_asset_supply_notes does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('property_asset_supply_notes')->orderBy('id')->get();

        if ($records->isEmpty()) {
            $this->warn('No supply request notes found. Skipping.');

            return;
        }

        $bar = $this->output->createProgressBar($records->count());
        $skipped = 0;

        foreach ($records as $row) {
            $supplyRequestExists = DB::table('supply_requests')->where('id', $row->property_asset_supply_id)->exists();
            if (! $supplyRequestExists) {
                $skipped++;
                $bar->advance();

                continue;
            }

            $createdBy = User::where('type', 'user')->where('second_reference_no', $row->created_by)->value('id');

            $data = [
                'id' => $row->id,
                'supply_request_id' => $row->property_asset_supply_id,
                'note' => $row->note,
                'created_by' => $createdBy ?? 1,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('supply_request_notes')->updateOrInsert(['id' => $row->id], $data);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} supply request notes.".($skipped ? " Skipped {$skipped} (missing parent supply request)." : ''));
    }

    private function migrateSupplyRequestImages(): void
    {
        $this->info('Migrating supply request images (property_asset_supply_images)...');

        if (! $this->tableExists('property_asset_supply_images')) {
            $this->warn('Source table property_asset_supply_images does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('property_asset_supply_images')->orderBy('id')->get();

        if ($records->isEmpty()) {
            $this->warn('No supply request images found. Skipping.');

            return;
        }

        $bar = $this->output->createProgressBar($records->count());
        $skipped = 0;

        foreach ($records as $row) {
            $supplyRequestId = $row->asset_supply_id;
            $supplyRequestExists = DB::table('supply_requests')->where('id', $supplyRequestId)->exists();

            if (! $supplyRequestExists) {
                $skipped++;
                $bar->advance();

                continue;
            }

            $data = [
                'id' => $row->id,
                'supply_request_id' => $supplyRequestId,
                'name' => $row->name,
                'path' => $row->path,
                'type' => $row->type ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('supply_request_images')->updateOrInsert(['id' => $row->id], $data);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} supply request images.".($skipped ? " Skipped {$skipped} (missing parent supply request)." : ''));
    }

    private function migrateComplaintCategories(): void
    {
        $this->info('Migrating complaint_categories...');

        if (! $this->tableExists('complaint_categories')) {
            $this->warn('Source table complaint_categories does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('complaint_categories')->get();
        if ($records->isEmpty()) {
            $this->warn('No complaint categories found. Skipping.');

            return;
        }

        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $data = [
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
            ];

            if (! $this->dryRun) {
                DB::table('complaint_categories')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} complaint categories.");
    }

    private function migrateComplaints(): void
    {
        $this->info('Migrating complaints...');

        if (! $this->tableExists('complaints')) {
            $this->warn('Source table complaints does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('complaints')->get();
        if ($records->isEmpty()) {
            $this->warn('No complaints found. Skipping.');

            return;
        }

        $defaultCategoryId = DB::connection('mysql2')->table('complaint_categories')->value('id');

        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $categoryId = $row->complaint_category_id ?? $row->category_id ?? $defaultCategoryId;
            if (! $categoryId) {
                $bar->advance();

                continue;
            }

            $data = [
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

            if (! $this->dryRun) {
                DB::table('complaints')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} complaints.");
    }

    private function migrateMaintenances(): void
    {
        $this->info('Migrating maintenances...');

        if (! $this->tableExists('maintenances')) {
            $this->warn('Source table maintenances does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('maintenances')->get();
        if ($records->isEmpty()) {
            $this->warn('No maintenance records found. Skipping.');

            return;
        }

        $bar = $this->output->createProgressBar($records->count());
        $skipped = 0;

        foreach ($records as $row) {
            $property = DB::table('properties')->where('id', $row->property_id)->first();
            if (! $property) {
                $skipped++;
                $bar->advance();

                continue;
            }

            $accountId = null;
            if (! empty($row->customer_id)) {
                $accountId = Account::where('second_reference_no', $row->customer_id)->value('id');
            }

            $status = $this->maintenanceStatusMap[$row->status ?? 'pending'] ?? 'pending';
            $priority = $this->priorityMap[$row->priority ?? 'Low'] ?? 'low';
            $segment = null;
            if (! empty($row->segment)) {
                $segment = $this->segmentMap[$row->segment] ?? strtolower($row->segment);
            }

            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $row->branch_id ?? 1,
                'property_id' => $row->property_id,
                'property_group_id' => $property->property_group_id ?? null,
                'property_building_id' => $property->property_building_id ?? null,
                'property_type_id' => $property->property_type_id ?? null,
                'rent_out_id' => $row->rentout_id ?? null,
                'account_id' => $accountId,
                'date' => $row->date ?? null,
                'time' => $row->time ?? null,
                'priority' => $priority,
                'segment' => $segment,
                'contact_person' => $row->contact_person ?? null,
                'contact_no' => $row->contact_no ?? null,
                'remark' => $row->remark ?? null,
                'company_remark' => $row->company_remark ?? null,
                'status' => $status,
                'created_by' => $row->created_by ?? 1,
                'completed_by' => $row->completed_by ?? null,
                'completed_at' => $row->completed_at ?? null,
                'updated_by' => $row->updated_by ?? null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
                'deleted_at' => $row->deleted_at ?? null,
            ];

            if (! $this->dryRun) {
                DB::table('maintenances')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} maintenances.".($skipped ? " Skipped {$skipped} (missing property)." : ''));
    }

    private function migrateMaintenanceComplaints(): void
    {
        $this->info('Migrating maintenance_complaints...');

        if (! $this->tableExists('maintenance_complaints')) {
            $this->warn('Source table maintenance_complaints does not exist. Skipping.');

            return;
        }

        $records = DB::connection('mysql2')->table('maintenance_complaints')->get();
        if ($records->isEmpty()) {
            $this->warn('No maintenance_complaints found. Skipping.');

            return;
        }

        $bar = $this->output->createProgressBar($records->count());
        $skipped = 0;

        foreach ($records as $row) {
            $maintenance = DB::table('maintenances')->where('id', $row->maintenance_id)->first();
            if (! $maintenance) {
                $skipped++;
                $bar->advance();

                continue;
            }

            $status = $this->maintenanceComplaintStatusMap[$row->status ?? 'pending'] ?? 'pending';

            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $row->branch_id ?? $maintenance->branch_id ?? 1,
                'maintenance_id' => $row->maintenance_id,
                'complaint_id' => $row->complaint_id ?? null,
                'status' => $status,
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

            if (! $this->dryRun) {
                DB::table('maintenance_complaints')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} maintenance complaints.".($skipped ? " Skipped {$skipped} (missing parent)." : ''));
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
