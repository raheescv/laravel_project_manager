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
        Schema::table('measurement_templates', function (Blueprint $table) {
            $table->dropForeign(['category_id']); // drop old FK
            $table->integer('category_id')->change(); // make plain integer
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('measurement_templates', function (Blueprint $table) {
            $table->foreign('category_id')
                  ->references('id')
                  ->on('measurement_categories')
                  ->onDelete('cascade');
        });
    }
};
