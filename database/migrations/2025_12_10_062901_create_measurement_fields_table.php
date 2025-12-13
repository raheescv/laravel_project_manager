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
        Schema::create('measurement_fields', function (Blueprint $table) { $table->id();
        $table->foreignId('measurement_template_id')->constrained()->onDelete('cascade');
        $table->string('key');
        $table->string('label');
        $table->boolean('required')->default(true);
        $table->timestamps();
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurement_fields');
    }
};
