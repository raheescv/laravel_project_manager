<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasColumn('units', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->index('tenant_id');
                // Remove unique constraints, make them unique per tenant
                $table->dropUnique(['name']);
                $table->dropUnique(['code']);
                $table->unique(['tenant_id', 'name']);
                $table->unique(['tenant_id', 'code']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasColumn('units', 'tenant_id')) {
                $table->dropUnique(['tenant_id', 'name']);
                $table->dropUnique(['tenant_id', 'code']);
                $table->unique(['name']);
                $table->unique(['code']);
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};
