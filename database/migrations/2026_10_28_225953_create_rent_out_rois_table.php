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
        Schema::create('rent_out_rois', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('rent_out_id')->constrained('rent_outs')->cascadeOnDelete();
            $table->date('date');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('no_of_months')->default(0);
            $table->decimal('sale_price', 16, 2)->default(0);
            $table->decimal('per_month_roi', 16, 2)->default(0);
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rent_out_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_out_rois');
    }
};
