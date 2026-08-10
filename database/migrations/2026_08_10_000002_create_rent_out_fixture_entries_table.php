<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('rent_out_fixture_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreignId('rent_out_fixture_area_id')->constrained('rent_out_fixture_areas')->cascadeOnDelete();
            $table->string('before_image_path')->nullable();
            $table->string('after_image_path')->nullable();
            $table->text('comments')->nullable();
            $table->string('status')->default('pending');
            $table->date('completed_date')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'rent_out_fixture_area_id'], 'rofe_tenant_area_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_out_fixture_entries');
    }
};
