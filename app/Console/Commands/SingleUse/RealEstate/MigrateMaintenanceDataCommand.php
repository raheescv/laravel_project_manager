<?php

namespace App\Console\Commands\SingleUse\RealEstate;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateMaintenanceDataCommand extends Command
{
    protected $signature = 'migrate:maintenance-data {--tenant= : Tenant ID to assign} {--dry-run : Run without inserting data}';

    protected $description = 'Migrate maintenance, complaint categories, complaints, and maintenance complaint records from accounts (mysql2) to project manager, preserving primary keys';

    private int $tenantId;

    private bool $dryRun;

    /**
     * Map old maintenance status to new enum value
     */
    private array $maintenanceStatusMap = [
        'pending' => 'pending',
        'completed' => 'completed',
        'rejected' => 'cancelled',
        'cancelled' => 'cancelled',
    ];

    /**
     * Map old priority to new enum value (lowercase)
     */
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

    /**
     * Map old segment to new enum value (lowercase)
     */
    private array $segmentMap = [
        'PPMC' => 'ppmc',
        'Corrective' => 'corrective',
        'Preparation' => 'preparation',
        'ppmc' => 'ppmc',
        'corrective' => 'corrective',
        'preparation' => 'preparation',
    ];

    /**
     * Map old maintenance_complaint status to new enum value
     */
    private array $maintenanceComplaintStatusMap = [
        'pending' => 'pending',
        'assigned' => 'assigned',
        'completed' => 'completed',
        'outstanding' => 'outstanding',
        'paid' => 'completed',
        'cancelled' => 'cancelled',
    ];

    public function handle(): int
    {
        $this->tenantId = (int) ($this->option('tenant') ?: 1);
        $this->dryRun = (bool) $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('DRY RUN MODE - No data will be inserted.');
        }

        $this->info("Starting maintenance data migration (tenant_id: {$this->tenantId})...");

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            $this->migrateComplaintCategories();
            $this->migrateComplaints();
            $this->migrateMaintenances();
            $this->migrateMaintenanceComplaints();
        } catch (\Exception $e) {
            $this->error("Migration failed: {$e->getMessage()}");
            Log::error('Maintenance data migration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return Command::FAILURE;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Maintenance data migration completed successfully.');

        return Command::SUCCESS;
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

        // Get a default category ID if needed (in case old records have no category)
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
            // Lookup property to auto-fill group/building/type
            $property = DB::table('properties')->where('id', $row->property_id)->first();
            if (! $property) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Map account_id: customer_id in accounts is account_head_id
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
        $this->info("Migrated {$records->count()} maintenances." . ($skipped ? " Skipped {$skipped} (missing property)." : ''));
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
            // Verify parent maintenance exists
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
        $this->info("Migrated {$records->count()} maintenance complaints." . ($skipped ? " Skipped {$skipped} (missing parent)." : ''));
    }

    /**
     * Check if a table exists in the source (mysql2) database
     */
    private function tableExists(string $table): bool
    {
        try {
            return DB::connection('mysql2')->getSchemaBuilder()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }
}
