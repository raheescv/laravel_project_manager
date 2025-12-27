<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds indexes to optimize employee report queries:
     * - sale_return_items: employee_id, product_id, sale_return_id, and composite indexes
     * - sale_returns: branch_id and composite index for date range queries
     * - sales: branch_id index (if not exists)
     */
    public function up(): void
    {
        // Indexes for sale_return_items table
        Schema::table('sale_return_items', function (Blueprint $table) {
            // Individual indexes for filtering and joins
            if (! $this->hasIndex('sale_return_items', 'sale_return_items_employee_id_index')) {
                $table->index('employee_id', 'sale_return_items_employee_id_index');
            }

            if (! $this->hasIndex('sale_return_items', 'sale_return_items_product_id_index')) {
                $table->index('product_id', 'sale_return_items_product_id_index');
            }

            if (! $this->hasIndex('sale_return_items', 'sale_return_items_sale_return_id_index')) {
                $table->index('sale_return_id', 'sale_return_items_sale_return_id_index');
            }

            // Composite index for GROUP BY queries (employee_id, product_id)
            if (! $this->hasIndex('sale_return_items', 'sale_return_items_employee_product_index')) {
                $table->index(['employee_id', 'product_id'], 'sale_return_items_employee_product_index');
            }
        });

        // Indexes for sale_returns table
        Schema::table('sale_returns', function (Blueprint $table) {
            // Branch index for filtering
            if (! $this->hasIndex('sale_returns', 'sale_returns_branch_id_index')) {
                $table->index('branch_id', 'sale_returns_branch_id_index');
            }

            // Composite index for date range + branch + status queries (most common filter combination)
            if (! $this->hasIndex('sale_returns', 'sale_returns_date_branch_status_index')) {
                $table->index(['date', 'branch_id', 'status'], 'sale_returns_date_branch_status_index');
            }
        });

        // Indexes for sales table
        Schema::table('sales', function (Blueprint $table) {
            // Branch index for filtering (if not exists)
            if (! $this->hasIndex('sales', 'sales_branch_id_index')) {
                $table->index('branch_id', 'sales_branch_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            if ($this->hasIndex('sale_return_items', 'sale_return_items_employee_id_index')) {
                $table->dropIndex('sale_return_items_employee_id_index');
            }
            if ($this->hasIndex('sale_return_items', 'sale_return_items_product_id_index')) {
                $table->dropIndex('sale_return_items_product_id_index');
            }
            if ($this->hasIndex('sale_return_items', 'sale_return_items_sale_return_id_index')) {
                $table->dropIndex('sale_return_items_sale_return_id_index');
            }
            if ($this->hasIndex('sale_return_items', 'sale_return_items_employee_product_index')) {
                $table->dropIndex('sale_return_items_employee_product_index');
            }
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            if ($this->hasIndex('sale_returns', 'sale_returns_branch_id_index')) {
                $table->dropIndex('sale_returns_branch_id_index');
            }
            if ($this->hasIndex('sale_returns', 'sale_returns_date_branch_status_index')) {
                $table->dropIndex('sale_returns_date_branch_status_index');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if ($this->hasIndex('sales', 'sales_branch_id_index')) {
                $table->dropIndex('sales_branch_id_index');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        try {
            $result = $connection->select(
                'SELECT COUNT(*) as count
                 FROM information_schema.statistics
                 WHERE table_schema = ?
                 AND table_name = ?
                 AND index_name = ?',
                [$databaseName, $table, $index]
            );

            return isset($result[0]) && $result[0]->count > 0;
        } catch (\Exception $e) {
            // If query fails, assume index doesn't exist
            return false;
        }
    }
};
