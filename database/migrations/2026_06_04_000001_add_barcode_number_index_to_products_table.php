<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $indexName = 'idx_products_tenant_barcode_number';

        if (! Schema::hasColumn('products', 'barcode_number')) {
            return;
        }

        if ($this->indexExists($indexName)) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($indexName) {
            $table->index(['tenant_id', 'barcode_number'], $indexName);
        });
    }

    public function down(): void
    {
        $indexName = 'idx_products_tenant_barcode_number';

        if (! $this->indexExists($indexName)) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function indexExists(string $indexName): bool
    {
        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = DATABASE()
             AND table_name = 'products'
             AND index_name = ?",
            [$indexName],
        );

        return $result[0]->count > 0;
    }
};
