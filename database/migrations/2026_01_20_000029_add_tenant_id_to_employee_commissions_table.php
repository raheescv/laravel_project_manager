<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('employee_commissions', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_commissions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->index('tenant_id');
                // Remove unique constraint, make it unique per tenant
                $table->dropUnique('unique_product_employee');
                $table->unique(['tenant_id', 'product_id', 'employee_id'], 'unique_tenant_product_employee');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_commissions', function (Blueprint $table) {
            if (Schema::hasColumn('employee_commissions', 'tenant_id')) {
                $table->dropUnique('unique_tenant_product_employee');
                $table->unique(['product_id', 'employee_id'], 'unique_product_employee');
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};
