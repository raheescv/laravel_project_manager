<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('property_service_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('property_group_id')->nullable();
            $table->unsignedBigInteger('property_building_id')->nullable();
            $table->unsignedBigInteger('property_type_id')->nullable();
            $table->unsignedBigInteger('rent_out_id')->nullable();
            $table->unsignedBigInteger('utility_id');
            $table->string('type');
            $table->date('date');
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('rent_out_service_status')->nullable();
            $table->string('status')->default('requested');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('financial_approved_by')->nullable();
            $table->dateTime('financial_approved_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->json('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id']);
            $table->index(['property_id', 'utility_id', 'type', 'date']);
            $table->index('status');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_service_requests');
    }
};
