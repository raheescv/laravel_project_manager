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
        Schema::table('tailoring_order_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('tailoring_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tailoring_order_items', function (Blueprint $table) {
            $table->dropColumn('rating');
        });
    }
};
