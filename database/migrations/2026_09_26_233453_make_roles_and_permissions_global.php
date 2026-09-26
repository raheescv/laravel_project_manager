<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles and permissions are one shared catalogue for every tenant; only a
 * user's role assignment is per tenant (users carry tenant_id). Each tenant
 * used to get its own copy of every row, so the copies are folded into the
 * lowest id per name — every role and user link is repointed first — and
 * the tenant_id column goes.
 */
return new class() extends Migration
{
    public function up(): void
    {
        $tables = config('permission.table_names');

        $this->fold($tables['permissions'], [
            [$tables['role_has_permissions'], 'permission_id', ['role_id']],
            [$tables['model_has_permissions'], 'permission_id', ['model_type', 'model_id']],
        ]);
        $this->fold($tables['roles'], [
            [$tables['role_has_permissions'], 'role_id', ['permission_id']],
            [$tables['model_has_roles'], 'role_id', ['model_type', 'model_id']],
        ]);

        foreach ([$tables['permissions'], $tables['roles']] as $table) {
            $this->dropTenantColumn($table);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tables = config('permission.table_names');

        foreach ([$tables['permissions'], $tables['roles']] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $blueprint->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $blueprint->index('tenant_id');
                $blueprint->dropUnique(['name', 'guard_name']);
                $blueprint->unique(['tenant_id', 'name', 'guard_name']);
            });
        }
    }

    /**
     * @param  list<array{0: string, 1: string, 2: list<string>}>  $pivots  [table, key column, the other key columns]
     */
    private function fold(string $table, array $pivots): void
    {
        $keepers = DB::table($table)->selectRaw('MIN(id) as id, name, guard_name')->groupBy('name', 'guard_name')->get();

        foreach ($keepers as $keeper) {
            $duplicateIds = DB::table($table)
                ->where('name', $keeper->name)->where('guard_name', $keeper->guard_name)
                ->where('id', '!=', $keeper->id)->pluck('id');

            foreach ($duplicateIds as $duplicateId) {
                foreach ($pivots as [$pivot, $column, $otherColumns]) {
                    $this->repoint($pivot, $column, $otherColumns, $duplicateId, $keeper->id);
                }
                DB::table($table)->where('id', $duplicateId)->delete();
            }
        }
    }

    /**
     * A link the keeper already has is dropped rather than moved, or the
     * pivot's primary key would collide.
     *
     * @param  list<string>  $otherColumns
     */
    private function repoint(string $pivot, string $column, array $otherColumns, int $fromId, int $toId): void
    {
        foreach (DB::table($pivot)->where($column, $fromId)->get() as $link) {
            $match = collect($otherColumns)->mapWithKeys(fn (string $other) => [$other => $link->{$other}])->all();

            if (DB::table($pivot)->where($column, $toId)->where($match)->exists()) {
                DB::table($pivot)->where($column, $fromId)->where($match)->delete();
            } else {
                DB::table($pivot)->where($column, $fromId)->where($match)->update([$column => $toId]);
            }
        }
    }

    private function dropTenantColumn(string $table): void
    {
        if (! Schema::hasColumn($table, 'tenant_id')) {
            return;
        }

        $foreignKeys = collect(Schema::getForeignKeys($table))->pluck('columns');
        $indexes = collect(Schema::getIndexes($table))->pluck('name');

        Schema::table($table, function (Blueprint $blueprint) use ($table, $foreignKeys, $indexes): void {
            if ($foreignKeys->contains(['tenant_id'])) {
                $blueprint->dropForeign(['tenant_id']);
            }
            if ($indexes->contains("{$table}_tenant_id_name_guard_name_unique")) {
                $blueprint->dropUnique(['tenant_id', 'name', 'guard_name']);
            }
            if ($indexes->contains("{$table}_tenant_id_index")) {
                $blueprint->dropIndex(['tenant_id']);
            }
            $blueprint->dropColumn('tenant_id');
            if (! $indexes->contains("{$table}_name_guard_name_unique")) {
                $blueprint->unique(['name', 'guard_name']);
            }
        });
    }
};
