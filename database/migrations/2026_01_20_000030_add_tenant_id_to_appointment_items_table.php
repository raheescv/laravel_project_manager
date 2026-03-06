<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('appointment_items', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

                // Check if the old unique index exists before trying to drop it
                $oldIndexExists = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics
                     WHERE table_schema = DATABASE()
                     AND table_name = 'appointment_items'
                     AND index_name = 'appointment_items_tenant_service_employee_unique'",
                );

                if ($oldIndexExists[0]->count > 0) {
                    $table->dropUnique(['tenant_id', 'appointment_id', 'service_id', 'employee_id']);
                }

                // Check if the new unique index already exists before creating it
                $newIndexExists = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics
                     WHERE table_schema = DATABASE()
                     AND table_name = 'appointment_items'
                     AND index_name = 'appointment_items_tenant_service_employee_unique'",
                );

                if ($newIndexExists[0]->count == 0) {
                    $table->unique(['tenant_id', 'appointment_id', 'service_id', 'employee_id'], 'appointment_items_tenant_service_employee_unique');
                }

                $table->index('tenant_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointment_items', function (Blueprint $table) {
            if (Schema::hasColumn('appointment_items', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};
