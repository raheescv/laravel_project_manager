<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('unique_no_counters', function (Blueprint $table) {
            if (! Schema::hasColumn('unique_no_counters', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('year');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->index('tenant_id');
                // Update primary key to include tenant_id
                $table->dropPrimary();
                $table->primary(['tenant_id', 'year', 'branch_code', 'segment']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('unique_no_counters', function (Blueprint $table) {
            if (Schema::hasColumn('unique_no_counters', 'tenant_id')) {
                $table->dropPrimary();
                $table->primary(['year', 'branch_code', 'segment']);
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};
