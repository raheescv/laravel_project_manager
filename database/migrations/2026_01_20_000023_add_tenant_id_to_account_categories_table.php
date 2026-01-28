<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('account_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('account_categories', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->index('tenant_id');
                // Remove unique constraint on name, make it unique per tenant
                $table->dropUnique(['name']);
                $table->unique(['tenant_id', 'name']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_categories', function (Blueprint $table) {
            if (Schema::hasColumn('account_categories', 'tenant_id')) {
                $table->dropUnique(['tenant_id', 'name']);
                $table->unique(['name']);
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};
