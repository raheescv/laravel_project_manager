<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        // --- PRODUCTS ---
        // 1. Add new columns
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode_prefix', 10)->nullable()->after('barcode');
            $table->string('barcode_number')->nullable()->after('barcode_prefix');
        });

        // 2. Migrate existing data
        DB::statement("UPDATE products SET barcode_number = barcode WHERE barcode IS NOT NULL");

        // 3. Set barcode_prefix from config for existing records
        $prefix = DB::table('configurations') ->where('key', 'barcode_prefix') ->value('value');

        if ($prefix) {
            DB::statement("UPDATE products SET barcode_prefix = ? WHERE barcode IS NOT NULL", [$prefix]);
        }

        // 4. Drop index that includes barcode, drop barcode column, re-create as storedAs
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_name_barcode');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode')->nullable()->storedAs("CONCAT(COALESCE(barcode_prefix, ''), barcode_number)")->after('time');
            $table->index(['tenant_id', 'name', 'barcode'], 'idx_products_name_barcode');
        });

        // --- INVENTORIES ---
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('barcode_prefix', 10)->nullable()->after('barcode');
            $table->string('barcode_number')->nullable()->after('barcode_prefix');
        });

        DB::statement("UPDATE inventories SET barcode_number = barcode WHERE barcode IS NOT NULL");

        if ($prefix) {
            DB::statement("UPDATE inventories SET barcode_prefix = ? WHERE barcode IS NOT NULL", [$prefix]);
        }

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->string('barcode')->nullable()->storedAs("CONCAT(COALESCE(barcode_prefix, ''), barcode_number)")->after('quantity');
        });

        // --- PRODUCT_UNITS ---
        Schema::table('product_units', function (Blueprint $table) {
            $table->string('barcode_prefix', 10)->nullable()->after('barcode');
            $table->string('barcode_number')->nullable()->after('barcode_prefix');
        });

        DB::statement("UPDATE product_units SET barcode_number = barcode WHERE barcode IS NOT NULL");

        if ($prefix) {
            DB::statement("UPDATE product_units SET barcode_prefix = ? WHERE barcode IS NOT NULL", [$prefix]);
        }

        Schema::table('product_units', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->string('barcode')->nullable()->storedAs("CONCAT(COALESCE(barcode_prefix, ''), barcode_number)")->after('conversion_factor');
        });
    }

    public function down(): void
    {
        // --- PRODUCTS ---
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_name_barcode');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('time');
            $table->index(['tenant_id', 'name', 'barcode'], 'idx_products_name_barcode');
        });

        DB::statement("UPDATE products SET barcode = barcode_number WHERE barcode_number IS NOT NULL");

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['barcode_prefix', 'barcode_number']);
        });

        // --- INVENTORIES ---
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('quantity');
        });

        DB::statement("UPDATE inventories SET barcode = barcode_number WHERE barcode_number IS NOT NULL");

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['barcode_prefix', 'barcode_number']);
        });

        // --- PRODUCT_UNITS ---
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('conversion_factor');
        });

        DB::statement("UPDATE product_units SET barcode = barcode_number WHERE barcode_number IS NOT NULL");

        Schema::table('product_units', function (Blueprint $table) {
            $table->dropColumn(['barcode_prefix', 'barcode_number']);
        });
    }
};
