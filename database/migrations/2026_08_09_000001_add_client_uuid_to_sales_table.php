<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offline selling on the mobile POS queues sales on the device and replays them
 * when connectivity returns. A replay must be safe: the app cannot tell a lost
 * response apart from a lost request, so it retries, and without an idempotency
 * key that retry creates a second sale.
 *
 * `client_uuid` is generated on the device the moment Charge is tapped and never
 * changes across retries, so the server can recognise a sale it has already
 * committed and return it instead of creating a duplicate.
 *
 * `client_created_at` is the till's own clock at that moment. It is kept for
 * audit and for ordering the sync queue only — never for accounting periods,
 * since a cashier can change a device clock.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->after('reference_no');
            $table->timestamp('client_created_at')->nullable()->after('date');
            // Composite with tenant_id: a uuid collision across tenants is
            // vanishingly unlikely, but uniqueness scoped to the tenant is the
            // rule everywhere else in this schema and the lookup always filters
            // by tenant anyway.
            $table->unique(['tenant_id', 'client_uuid'], 'sales_tenant_client_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_tenant_client_uuid_unique');
            $table->dropColumn(['client_uuid', 'client_created_at']);
        });
    }
};
