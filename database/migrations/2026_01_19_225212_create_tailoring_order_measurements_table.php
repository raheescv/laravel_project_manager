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
        Schema::create('tailoring_order_measurements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unsignedBigInteger('tailoring_order_id');
            $table->foreign('tailoring_order_id', 'tom_order_fk')->references('id')->on('tailoring_orders')->onDelete('cascade');
            $table->unsignedBigInteger('tailoring_category_id')->nullable();
            $table->foreign('tailoring_category_id', 'tom_cat_fk')->references('id')->on('tailoring_categories')->onDelete('set null');
            $table->unsignedBigInteger('tailoring_category_model_id')->nullable();
            $table->foreign('tailoring_category_model_id', 'tom_cat_model_fk')->references('id')->on('tailoring_category_models')->onDelete('set null');

            $table->json('data')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'tailoring_order_id'], 'tom_tenant_order_idx');
            $table->index(['tenant_id', 'tailoring_category_id'], 'tom_tenant_cat_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tailoring_order_measurements');
    }
};
