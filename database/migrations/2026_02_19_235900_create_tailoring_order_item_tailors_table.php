<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('tailoring_order_item_tailors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('tailoring_order_item_id');
            $table->foreign('tailoring_order_item_id')->references('id')->on('tailoring_order_items')->onDelete('cascade');

            $table->unsignedBigInteger('tailor_id')->nullable();
            $table->foreign('tailor_id')->references('id')->on('users')->nullOnDelete();
            $table->decimal('tailor_commission', 10, 2)->default(0);
            $table->date('completion_date')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->enum('status', ['pending', 'completed', 'delivered'])->default('pending');

            $table->unsignedBigInteger('created_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'tailoring_order_item_id'], 'toit_tenant_item_idx');
            $table->index(['tenant_id', 'tailor_id'], 'toit_tenant_tailor_idx');
            $table->index(['tenant_id', 'status'], 'toit_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailoring_order_item_tailors');
    }
};
