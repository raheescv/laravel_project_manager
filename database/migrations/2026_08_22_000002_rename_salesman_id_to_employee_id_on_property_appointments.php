<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * salesman_id becomes employee_id.
     *
     * The appointment no longer inherits whoever sits on rent_outs.salesman_id —
     * the person carrying out the appointment is now chosen on the appointment
     * itself, so the column is named after what it holds: an employee.
     *
     * The rename is not a one-liner because active_slot_key — the STORED
     * generated column that carries the no-double-booking unique index — is
     * defined in terms of this column. MySQL refuses to rename a column a
     * generated column depends on, so the index and the generated column are
     * dropped, the rename happens, and both are rebuilt against the new name.
     */
    public function up(): void
    {
        if (! Schema::hasTable('property_appointments') || Schema::hasColumn('property_appointments', 'employee_id')) {
            return;
        }

        if ($this->hasIndex('pa_active_slot_unique')) {
            DB::statement('ALTER TABLE property_appointments DROP INDEX pa_active_slot_unique');
        }

        if (Schema::hasColumn('property_appointments', 'active_slot_key')) {
            DB::statement('ALTER TABLE property_appointments DROP COLUMN active_slot_key');
        }

        DB::statement('ALTER TABLE property_appointments RENAME COLUMN salesman_id TO employee_id');

        if ($this->hasIndex('pa_tenant_salesman_sched_idx')) {
            DB::statement('ALTER TABLE property_appointments RENAME INDEX pa_tenant_salesman_sched_idx TO pa_tenant_employee_sched_idx');
        }

        DB::statement("
            ALTER TABLE property_appointments
            ADD COLUMN active_slot_key VARCHAR(64)
            GENERATED ALWAYS AS (
                CASE
                    WHEN status IN ('scheduled', 'completed') AND deleted_at IS NULL AND scheduled_at IS NOT NULL
                    THEN CONCAT(employee_id, '@', scheduled_at)
                    ELSE NULL
                END
            ) STORED
        ");

        DB::statement('
            ALTER TABLE property_appointments
            ADD UNIQUE INDEX pa_active_slot_unique (tenant_id, active_slot_key)
        ');
    }

    public function down(): void
    {
        if (! Schema::hasTable('property_appointments') || ! Schema::hasColumn('property_appointments', 'employee_id')) {
            return;
        }

        if ($this->hasIndex('pa_active_slot_unique')) {
            DB::statement('ALTER TABLE property_appointments DROP INDEX pa_active_slot_unique');
        }

        if (Schema::hasColumn('property_appointments', 'active_slot_key')) {
            DB::statement('ALTER TABLE property_appointments DROP COLUMN active_slot_key');
        }

        DB::statement('ALTER TABLE property_appointments RENAME COLUMN employee_id TO salesman_id');

        if ($this->hasIndex('pa_tenant_employee_sched_idx')) {
            DB::statement('ALTER TABLE property_appointments RENAME INDEX pa_tenant_employee_sched_idx TO pa_tenant_salesman_sched_idx');
        }

        DB::statement("
            ALTER TABLE property_appointments
            ADD COLUMN active_slot_key VARCHAR(64)
            GENERATED ALWAYS AS (
                CASE
                    WHEN status IN ('scheduled', 'completed') AND deleted_at IS NULL AND scheduled_at IS NOT NULL
                    THEN CONCAT(salesman_id, '@', scheduled_at)
                    ELSE NULL
                END
            ) STORED
        ");

        DB::statement('
            ALTER TABLE property_appointments
            ADD UNIQUE INDEX pa_active_slot_unique (tenant_id, active_slot_key)
        ');
    }

    private function hasIndex(string $name): bool
    {
        return count(DB::select('SHOW INDEX FROM property_appointments WHERE Key_name = ?', [$name])) > 0;
    }
};
