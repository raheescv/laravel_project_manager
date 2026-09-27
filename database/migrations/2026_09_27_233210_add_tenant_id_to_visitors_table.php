<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Existing rows are backfilled from the visiting user's tenant, falling
     * back to the branch's tenant for anonymous hits.
     */
    public function up(): void
    {
        if (Schema::hasColumn('visitors', 'tenant_id')) {
            return;
        }

        Schema::table('visitors', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->index(['tenant_id', 'visited_at'], 'visitors_tenant_visited_at_index');
        });

        DB::table('visitors')
            ->join('users', 'users.id', '=', 'visitors.user_id')
            ->whereNull('visitors.tenant_id')
            ->update(['visitors.tenant_id' => DB::raw('users.tenant_id')]);

        DB::table('visitors')
            ->join('branches', 'branches.id', '=', 'visitors.branch_id')
            ->whereNull('visitors.tenant_id')
            ->update(['visitors.tenant_id' => DB::raw('branches.tenant_id')]);
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            if (Schema::hasColumn('visitors', 'tenant_id')) {
                $table->dropIndex('visitors_tenant_visited_at_index');
                $table->dropColumn('tenant_id');
            }
        });
    }
};
