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
        Schema::table('unique_no_counters', function (Blueprint $table) {
            $table->string('branch_code', 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unique_no_counters', function (Blueprint $table) {
            $table->string('branch_code', 5)->change();
        });
    }
};
