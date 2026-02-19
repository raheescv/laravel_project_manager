<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('tailoring_order_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('tailoring_order_items', 'inventory_id')) {
                $table->unsignedBigInteger('inventory_id')->nullable()->after('tailoring_category_model_type_id');
                $table->foreign('inventory_id')->references('id')->on('inventories')->nullOnDelete();
                $table->index('inventory_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tailoring_order_items', function (Blueprint $table): void {
            if (Schema::hasColumn('tailoring_order_items', 'inventory_id')) {
                $table->dropForeign(['inventory_id']);
                $table->dropIndex(['inventory_id']);
                $table->dropColumn('inventory_id');
            }
        });
    }
};
