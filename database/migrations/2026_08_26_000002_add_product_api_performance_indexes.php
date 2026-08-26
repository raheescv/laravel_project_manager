<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the public product API (GET /api/v1/products).
 *
 * The list endpoint's cost was dominated by two lookups that had no usable
 * index: the inventories eager-load / stock aggregate (only `tenant_id` was
 * indexed, so every page scanned the whole table) and the product-image
 * thumbnail lookup (no index at all — a full table scan per page).
 */
return new class() extends Migration
{
    /**
     * [table, index name, columns]
     */
    private array $indexes = [
        // Stock aggregates and the in_stock_only / branch_id filters all look up
        // inventories by product, optionally narrowed to one branch. `quantity`
        // and `deleted_at` ride along so the lookup is covering and never has to
        // touch the row — that alone halves the in_stock_only count.
        ['inventories', 'idx_inventories_tenant_product_branch', ['tenant_id', 'product_id', 'branch_id', 'quantity', 'deleted_at']],

        // A barcode scan. `products.barcode` is a stored generated column and the
        // only index carrying it put `name` in front, so a scan walked the whole
        // catalog for one row — the hottest lookup the POS makes.
        ['products', 'idx_products_tenant_barcode', ['tenant_id', 'barcode']],

        // Card thumbnail: "first normal image for these products".
        ['product_images', 'idx_product_images_product_method', ['product_id', 'method']],

        // Catalog browse: type / category filter with the default name sort, so
        // the filter and the ORDER BY are served by one index and no filesort
        // runs. `deleted_at` sits before `name` because SoftDeletes adds an
        // IS NULL check to every one of these queries.
        ['products', 'idx_products_tenant_type_name', ['tenant_id', 'type', 'deleted_at', 'name']],
        ['products', 'idx_products_tenant_category_name', ['tenant_id', 'main_category_id', 'deleted_at', 'name']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$table, $name, $columns]) {
            if (! Schema::hasTable($table) || $this->indexExists($table, $name)) {
                continue;
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue 2;
                }
            }
            Schema::table($table, function (Blueprint $blueprint) use ($name, $columns) {
                $blueprint->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as [$table, $name, $columns]) {
            if (! Schema::hasTable($table) || ! $this->indexExists($table, $name)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($name) {
                $blueprint->dropIndex($name);
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select(
            'SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName],
        );

        return $result[0]->count > 0;
    }
};
