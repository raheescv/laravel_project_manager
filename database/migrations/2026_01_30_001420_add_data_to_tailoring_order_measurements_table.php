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
        Schema::table('tailoring_order_measurements', function (Blueprint $table) {
            if (! Schema::hasColumn('tailoring_order_measurements', 'data')) {
                $table->json('data')->nullable()->after('tailoring_category_model_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tailoring_order_measurements', function (Blueprint $table) {
            if (Schema::hasColumn('tailoring_order_measurements', 'data')) {
                $table->dropColumn('data');
            }
        });
    }
};
