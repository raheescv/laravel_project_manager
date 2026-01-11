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
        Schema::table('sales', function (Blueprint $table) {
             $table->string('category_id')->nullable()->change();
            $table->string('sub_category_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
             $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->unsignedBigInteger('sub_category_id')->nullable()->change();
        });
    }
};
