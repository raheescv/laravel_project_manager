<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

                // Check if the old unique index exists before trying to drop it
                $indexExists = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics
                     WHERE table_schema = DATABASE()
                     AND table_name = 'products'
                     AND index_name = 'products_name_type_unique'",
                );

                if ($indexExists[0]->count > 0) {
                    $table->dropUnique(['name', 'type']);
                }

                // Check if the new unique index already exists before creating it
                $newIndexExists = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics
                     WHERE table_schema = DATABASE()
                     AND table_name = 'products'
                     AND index_name = 'product_tenant_name_type_index'",
                );

                if ($newIndexExists[0]->count == 0) {
                    $table->unique(['tenant_id', 'name', 'type'], 'product_tenant_name_type_index');
                }

                $table->index('tenant_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};
