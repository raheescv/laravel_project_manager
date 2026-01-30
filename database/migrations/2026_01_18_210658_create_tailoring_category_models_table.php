<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('tailoring_category_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unsignedBigInteger('tailoring_category_id');
            $table->foreign('tailoring_category_id')->references('id')->on('tailoring_categories')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'tailoring_category_id', 'name'], 't_c_m_tenant_category_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailoring_category_models');
    }
};
