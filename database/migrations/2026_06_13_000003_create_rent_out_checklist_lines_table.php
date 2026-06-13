<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('rent_out_checklist_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreignId('rent_out_id')->constrained('rent_outs')->cascadeOnDelete();
            $table->unsignedBigInteger('checklist_id')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('qty')->nullable()->default(1);
            $table->string('move_in_status')->nullable();
            $table->string('move_in_comment')->nullable();
            $table->string('move_out_status')->nullable();
            $table->string('move_out_comment')->nullable();
            $table->decimal('damage_cost', 16, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'rent_out_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_out_checklist_lines');
    }
};
