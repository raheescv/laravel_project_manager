<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_items', 'base_unit_quantity')) {
                $table->decimal('base_unit_quantity', 16, 4)->storedAs('quantity * conversion_factor')->after('quantity');
            }
        });
        Schema::table('purchase_return_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_return_items', 'base_unit_quantity')) {
                $table->decimal('base_unit_quantity', 16, 4)->storedAs('quantity * conversion_factor')->after('quantity');
            }
        });
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'base_unit_quantity')) {
                $table->decimal('base_unit_quantity', 16, 4)->storedAs('quantity * conversion_factor')->after('quantity');
            }
        });
        Schema::table('sale_return_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_return_items', 'base_unit_quantity')) {
                $table->decimal('base_unit_quantity', 16, 4)->storedAs('quantity * conversion_factor')->after('quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'base_unit_quantity')) {
                $table->dropColumn('base_unit_quantity');
            }
        });
        Schema::table('purchase_return_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_return_items', 'base_unit_quantity')) {
                $table->dropColumn('base_unit_quantity');
            }
        });
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'base_unit_quantity')) {
                $table->dropColumn('base_unit_quantity');
            }
        });
        Schema::table('sale_return_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_return_items', 'base_unit_quantity')) {
                $table->dropColumn('base_unit_quantity');
            }
        });
    }
};
