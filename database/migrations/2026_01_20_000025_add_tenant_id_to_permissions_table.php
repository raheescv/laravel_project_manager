<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            return; // Skip if permissions not configured
        }

        // Add tenant_id to permissions table
        if (Schema::hasTable($tableNames['permissions'])) {
            Schema::table($tableNames['permissions'], function (Blueprint $table) use ($tableNames) {
                if (! Schema::hasColumn($tableNames['permissions'], 'tenant_id')) {
                    $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                    $table->index('tenant_id');
                    // Update unique constraint to include tenant_id
                    $table->dropUnique(['name', 'guard_name']);
                    $table->unique(['tenant_id', 'name', 'guard_name']);
                }
            });
        }

        // Add tenant_id to roles table
        if (Schema::hasTable($tableNames['roles'])) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($tableNames) {
                if (! Schema::hasColumn($tableNames['roles'], 'tenant_id')) {
                    $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                    $table->index('tenant_id');
                    // Update unique constraint to include tenant_id
                    $teams = config('permission.teams');
                    $columnNames = config('permission.column_names');

                    if ($teams && isset($columnNames['team_foreign_key'])) {
                        // If teams are enabled, we need to handle the unique constraint differently
                        // For now, just add tenant_id - the unique constraint might need manual adjustment
                    } else {
                        $table->dropUnique(['name', 'guard_name']);
                        $table->unique(['tenant_id', 'name', 'guard_name']);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            return;
        }

        if (Schema::hasTable($tableNames['permissions'])) {
            Schema::table($tableNames['permissions'], function (Blueprint $table) use ($tableNames) {
                if (Schema::hasColumn($tableNames['permissions'], 'tenant_id')) {
                    $table->dropUnique(['tenant_id', 'name', 'guard_name']);
                    $table->unique(['name', 'guard_name']);
                    $table->dropForeign(['tenant_id']);
                    $table->dropIndex(['tenant_id']);
                    $table->dropColumn('tenant_id');
                }
            });
        }

        if (Schema::hasTable($tableNames['roles'])) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($tableNames) {
                if (Schema::hasColumn($tableNames['roles'], 'tenant_id')) {
                    $teams = config('permission.teams');
                    if (! $teams) {
                        $table->dropUnique(['tenant_id', 'name', 'guard_name']);
                        $table->unique(['name', 'guard_name']);
                    }
                    $table->dropForeign(['tenant_id']);
                    $table->dropIndex(['tenant_id']);
                    $table->dropColumn('tenant_id');
                }
            });
        }
    }
};
