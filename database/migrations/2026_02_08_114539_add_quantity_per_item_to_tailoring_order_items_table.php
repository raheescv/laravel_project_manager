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
        Schema::table('tailoring_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('tailoring_order_items', 'quantity_per_item')) {
                $table->decimal('quantity_per_item', 8, 3)->default(1)->after('quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tailoring_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('tailoring_order_items', 'quantity_per_item')) {
                $table->dropColumn('quantity_per_item');
            }
        });
    }
};
