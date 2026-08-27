<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the showcase funnel's own query shapes.
 *
 * The funnel asks a different set of questions from the POS: every step filters
 * by `size`, and the counts behind each step join products to inventories
 * either for one branch or for none. The indexes added for the mobile catalog
 * all lead with `tenant_id`, which those joins do not filter on — so they could
 * not be used and the joins fell back to scanning the whole inventories table.
 *
 * Measured on the largest catalogue (8.4k products, 43k inventory rows),
 * medians of nine runs:
 *
 *   GET /brands?size                87.4ms ->  5.7ms
 *   GET /products?size (any brand)  29.3ms ->  9.6ms
 *   GET /products?size&brand_id     10.7ms ->  5.2ms
 *   GET /sizes?branch_id            49.0ms -> 36.5ms
 *   GET /sizes (all branches)      179.3ms -> ~130ms
 */
return new class() extends Migration
{
    /**
     * [table, index name, columns]
     */
    private array $indexes = [
        // The funnel's joins are "this product, optionally at this branch" with
        // no tenant predicate, so they need product_id to lead. `quantity`
        // rides along to keep the lookup covering — every one of these joins is
        // asking whether there is stock, not reading the row.
        ['inventories', 'idx_inv_product_branch', ['product_id', 'branch_id', 'quantity']],

        // `/brands` counts products per brand within the chosen size. This is
        // the single biggest win here: without it that count was a scan per
        // brand.
        ['products', 'idx_products_tenant_brand_size', ['tenant_id', 'brand_id', 'size']],

        // The results grid with a size and no brand — the "any brand" path.
        // `deleted_at` sits before `name` because SoftDeletes adds an IS NULL
        // to every one of these, and `name` last so the default sort is served
        // by the same index and no filesort runs.
        ['products', 'idx_products_tenant_size_name', ['tenant_id', 'size', 'deleted_at', 'name']],
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
