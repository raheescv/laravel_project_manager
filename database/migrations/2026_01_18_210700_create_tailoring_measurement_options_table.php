<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('tailoring_measurement_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->string('option_type');
            $table->string('value');
            $table->timestamps();
            $table->index(['tenant_id', 'option_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailoring_measurement_options');
    }
};
