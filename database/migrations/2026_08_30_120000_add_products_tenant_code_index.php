<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index `products.code`.
 *
 * The table carried eighteen indexes and not one led on `code`, while four call
 * sites look a product up by exactly that, as an equality match under the tenant
 * scope — so each was reading the whole catalog:
 *
 *   - Product::generateUniqueCode() spins `where('code', …)->exists()` in a
 *     do-while until it finds a free code. At least one full scan per product
 *     created, and an import creating thousands pays it thousands of times.
 *   - ProductResource::getAvailableSizes() on the detail route.
 *   - SaleImport resolves each row's product by code (memoised per import run).
 *   - Livewire\Product\Page pulls related products by shared code.
 *
 * Tenant-first to match every other composite here, since the scope is always
 * applied and `code` is only unique within a tenant.
 *
 * Note this is NOT for the API's `barcode` filter: a scan carries a barcode, and
 * barcode and code are deliberately separate fields that are not searched
 * together. `barcode` has its own index already (idx_products_tenant_barcode).
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['tenant_id', 'code'], 'idx_products_tenant_code');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_tenant_code');
        });
    }
};
