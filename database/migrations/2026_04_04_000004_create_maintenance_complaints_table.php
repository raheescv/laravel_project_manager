<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('maintenance_id');
            $table->unsignedBigInteger('complaint_id')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->text('technician_remark')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id']);
            $table->index('maintenance_id');
            $table->index('complaint_id');
            $table->index('technician_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_complaints');
    }
};
