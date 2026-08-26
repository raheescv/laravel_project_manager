<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Where a sale came from — the web screens, the mobile app's API, an import, and
 * so on. Stamped once at creation and never changed afterwards, so a sale can
 * always be traced back to the channel that produced it (see saleSources()).
 *
 * Nullable on purpose: rows created before this column existed genuinely have an
 * unknown origin and are shown as such, rather than being back-filled with a
 * guess. The one exception is a sale carrying `client_uuid`, which only the
 * mobile POS ever sets — those are known to have arrived over the API.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('source', 20)->nullable()->after('sale_type');
            $table->index(['tenant_id', 'source'], 'sales_tenant_source_index');
        });

        DB::table('sales')->whereNotNull('client_uuid')->update(['source' => 'api']);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_tenant_source_index');
            $table->dropColumn('source');
        });
    }
};
