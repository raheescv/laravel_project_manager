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
            $table->text('tailoring_notes')->nullable()->after('mobile_pocket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tailoring_order_measurements', function (Blueprint $table) {
            $table->dropColumn('tailoring_notes');
        });
    }
};
