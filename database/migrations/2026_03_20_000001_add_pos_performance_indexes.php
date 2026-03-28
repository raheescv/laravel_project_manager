<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table): void {
            if (! $this->hasIndex('inventories', 'idx_inventories_branch_employee')) {
                $table->index(['tenant_id', 'branch_id', 'employee_id'], 'idx_inventories_branch_employee');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (! $this->hasIndex('products', 'idx_products_selling_category')) {
                $table->index(['tenant_id', 'is_selling', 'main_category_id'], 'idx_products_selling_category');
            }
            if (! $this->hasIndex('products', 'idx_products_name_barcode')) {
                $table->index(['tenant_id', 'name', 'barcode'], 'idx_products_name_barcode');
            }
        });

        Schema::table('categories', function (Blueprint $table): void {
            if (! $this->hasIndex('categories', 'idx_categories_sale_visibility')) {
                $table->index(['tenant_id', 'sale_visibility_flag'], 'idx_categories_sale_visibility');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table): void {
            if ($this->hasIndex('inventories', 'idx_inventories_branch_employee')) {
                $table->dropIndex('idx_inventories_branch_employee');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if ($this->hasIndex('products', 'idx_products_selling_category')) {
                $table->dropIndex('idx_products_selling_category');
            }
            if ($this->hasIndex('products', 'idx_products_name_barcode')) {
                $table->dropIndex('idx_products_name_barcode');
            }
        });

        Schema::table('categories', function (Blueprint $table): void {
            if ($this->hasIndex('categories', 'idx_categories_sale_visibility')) {
                $table->dropIndex('idx_categories_sale_visibility');
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))->contains('name', $indexName);
    }
};
