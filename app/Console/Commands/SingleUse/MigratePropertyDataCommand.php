<?php

namespace App\Console\Commands\SingleUse;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigratePropertyDataCommand extends Command
{
    protected $signature = 'migrate:property-data {--tenant= : Tenant ID to assign} {--dry-run : Run without inserting data}';

    protected $description = 'Migrate property and rent-out data from accounts (mysql2) to project manager, preserving primary keys';

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
            $this->migratePropertyGroups();
            $this->migratePropertyBuildings();
            $this->migratePropertyTypes();
            $this->migrateProperties();
            $this->migrateRentOuts();
            $this->migrateRentOutSecurities();
            $this->migrateRentOutExtends();
            $this->migrateRentOutCheques();
            $this->migrateRentOutUtilities();
            $this->migrateRentOutUtilityTerms();
            $this->migrateRentOutServices();
            $this->migrateRentOutNotes();
            $this->migrateRentOutPaymentTerms();
            $this->migrateTenantDetails();
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
                'lease_agreement_no' => null,
                'year' => $row->lease_agreement_years ?? null,
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
                'account_id' => $row->account_head_id ?? null,
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
                'name' => $row->number ?? "Property-{$row->id}",
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
                'account_id' => $row->customer_id,
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
                'management_fee_payment_mode' => $this->resolvePaymentMode($row->management_fee_payment_mode_id ?? null),
                'management_fee_remarks' => $row->management_fee_remarks ?? null,
                'down_payment' => $row->down_payment ?? 0,
                'down_payment_mode' => $this->resolvePaymentMode($row->down_payment_mode_id ?? null),
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
                'deleted_at' => $row->deleted_at ?? null,
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

    private function migrateRentOutUtilities(): void
    {
        $this->info('Migrating rentout_utilities -> rent_out_utilities...');
        $records = DB::connection('mysql2')->table('rentout_utilities')->get();
        $bar = $this->output->createProgressBar($records->count());

        // Get utility names from the utilities table
        $utilityNames = DB::connection('mysql2')
            ->table('utilities')
            ->pluck('name', 'id')
            ->toArray();

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
                'utility_id' => $row->utility_id ?? null,
                'name' => $utilityNames[$row->utility_id] ?? null,
                'description' => null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('rent_out_utilities')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} rent out utilities.");
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

            // Map old utility_id to the rent_out_utilities record
            $rentOutUtilityId = DB::connection('mysql2')
                ->table('rentout_utilities')
                ->where('rentout_id', $row->rentout_id)
                ->where('utility_id', $row->utility_id)
                ->value('id');

            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => $branchId,
                'rent_out_id' => $row->rentout_id,
                'rent_out_utility_id' => $rentOutUtilityId ?? 0,
                'amount' => $row->amount ?? 0,
                'balance' => $row->balance ?? 0,
                'date' => $row->date ?? null,
                'remarks' => null,
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
        $this->info('Migrating rentout_services -> rent_out_services...');
        // In old project, rentout_services is just a lookup table (id, name)
        // In new project, rent_out_services belongs to a rent_out with amount
        // We'll migrate the service names as standalone records
        $records = DB::connection('mysql2')->table('rentout_services')->get();
        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $row) {
            $data = [
                'id' => $row->id,
                'tenant_id' => $this->tenantId,
                'branch_id' => 1,
                'rent_out_id' => 0, // No rent_out association in old schema
                'name' => $row->name,
                'amount' => 0,
                'description' => null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (! $this->dryRun) {
                DB::table('rent_out_services')->updateOrInsert(['id' => $row->id], $data);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$records->count()} rent out services.");
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
                'due_date' => $row->date,
                'paid_date' => null,
                'status' => ($row->paid ?? 0) >= ($row->amount ?? 0) && ($row->amount ?? 0) > 0 ? 'paid' : 'pending',
                'remarks' => $row->remark ?? null,
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
}
