<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The customer view (and every per-customer statement / report) filters sales and
 * sale returns by account_id, but neither table had an index on it — so each of
 * those queries scanned every row belonging to the tenant. The composite index
 * matches the actual predicate order: tenant scope, then account, then date range.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['tenant_id', 'account_id', 'date'], 'sales_tenant_account_date_index');
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->index(['tenant_id', 'account_id', 'date'], 'sale_returns_tenant_account_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_tenant_account_date_index');
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropIndex('sale_returns_tenant_account_date_index');
        });
    }
};
