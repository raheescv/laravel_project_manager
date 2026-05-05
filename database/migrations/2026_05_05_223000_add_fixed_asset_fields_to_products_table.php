<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'item_no')) {
                $table->string('item_no')->nullable()->after('part_no');
            }
            if (! Schema::hasColumn('products', 'supplier_name')) {
                $table->string('supplier_name')->nullable()->after('item_no');
            }
            if (! Schema::hasColumn('products', 'purchase_date')) {
                $table->date('purchase_date')->nullable()->after('supplier_name');
            }
            if (! Schema::hasColumn('products', 'duration')) {
                $table->decimal('duration', 12, 2)->nullable()->after('purchase_date');
            }
            if (! Schema::hasColumn('products', 'duration_period')) {
                $table->string('duration_period')->nullable()->after('duration');
            }
            if (! Schema::hasColumn('products', 'depreciation_method')) {
                $table->string('depreciation_method')->nullable()->after('duration_period');
            }
            if (! Schema::hasColumn('products', 'declining_factor')) {
                $table->decimal('declining_factor', 12, 2)->nullable()->after('depreciation_method');
            }
            if (! Schema::hasColumn('products', 'depreciation_amount')) {
                $table->decimal('depreciation_amount', 12, 2)->nullable()->after('declining_factor');
            }
            if (! Schema::hasColumn('products', 'prorata_date')) {
                $table->date('prorata_date')->nullable()->after('depreciation_amount');
            }
        });

        DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('product','service','asset') NOT NULL DEFAULT 'product'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('product','service') NOT NULL DEFAULT 'product'");

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'item_no')) {
                $table->dropColumn('item_no');
            }
            if (Schema::hasColumn('products', 'supplier_name')) {
                $table->dropColumn('supplier_name');
            }
            if (Schema::hasColumn('products', 'purchase_date')) {
                $table->dropColumn('purchase_date');
            }
            if (Schema::hasColumn('products', 'duration')) {
                $table->dropColumn('duration');
            }
            if (Schema::hasColumn('products', 'duration_period')) {
                $table->dropColumn('duration_period');
            }
            if (Schema::hasColumn('products', 'depreciation_method')) {
                $table->dropColumn('depreciation_method');
            }
            if (Schema::hasColumn('products', 'declining_factor')) {
                $table->dropColumn('declining_factor');
            }
            if (Schema::hasColumn('products', 'depreciation_amount')) {
                $table->dropColumn('depreciation_amount');
            }
            if (Schema::hasColumn('products', 'prorata_date')) {
                $table->dropColumn('prorata_date');
            }
        });
    }
};
