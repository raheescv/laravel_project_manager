<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('rent_out_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('rent_out_id')->constrained('rent_outs')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount', 16, 2)->default(0);
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('no_of_days')->nullable();
            $table->unsignedInteger('no_of_months')->nullable();
            $table->decimal('unit_size', 12, 2)->nullable();
            $table->decimal('per_square_meter_price', 12, 2)->nullable();
            $table->decimal('per_day_price', 12, 2)->nullable();
            $table->string('reason')->nullable();
            $table->string('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'rent_out_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_out_services');
    }
};
