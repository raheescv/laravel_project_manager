<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('issue_items', 'inventory_id')) {
            Schema::table('issue_items', function (Blueprint $table) {
                $table->unsignedBigInteger('inventory_id')->nullable()->after('product_id');
                $table->foreign('inventory_id')->references('id')->on('inventories')->nullOnDelete();
                $table->index('inventory_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('issue_items', 'inventory_id')) {
            Schema::table('issue_items', function (Blueprint $table) {
                $table->dropForeign(['inventory_id']);
                $table->dropIndex(['inventory_id']);
                $table->dropColumn('inventory_id');
            });
        }
    }
};
