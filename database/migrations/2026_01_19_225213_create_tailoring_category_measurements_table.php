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
        Schema::create('tailoring_category_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('tailoring_category_id')->constrained()->onDelete('cascade');
            $table->string('field_key'); // e.g. length, shoulder, collar_type
            $table->string('label'); // e.g. Length, Shoulder, Collar Type
            $table->string('field_type')->default('input'); // input, select
            $table->string('options_source')->nullable(); // For select fields, e.g. 'collar', 'cuff'
            $table->string('section')->nullable(); // basic_body, collar_cuff, specifications
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tailoring_category_measurements');
    }
};
