<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'unit_id')) {
                $table->foreignId('unit_id')->default(1)->after('product_id')->constrained('units');
            }
            if (! Schema::hasColumn('sale_items', 'conversion_factor')) {
                $table->decimal('conversion_factor', 15, 4)->default(1)->after('unit_id');
            }
        });
        Schema::table('sale_return_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_return_items', 'unit_id')) {
                $table->foreignId('unit_id')->default(1)->after('product_id')->constrained('units');
            }
            if (! Schema::hasColumn('sale_return_items', 'conversion_factor')) {
                $table->decimal('conversion_factor', 15, 4)->default(1)->after('unit_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }
            if (Schema::hasColumn('sale_items', 'conversion_factor')) {
                $table->dropColumn('conversion_factor');
            }
        });
        Schema::table('sale_return_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_return_items', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }
            if (Schema::hasColumn('sale_return_items', 'conversion_factor')) {
                $table->dropColumn('conversion_factor');
            }
        });
    }
};
