<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'sale_combo_offer_id')) {
                $table->unsignedBigInteger('sale_combo_offer_id')->nullable()->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'sale_combo_offer_id')) {
                $table->dropColumn('sale_combo_offer_id');
            }
        });
    }
};
