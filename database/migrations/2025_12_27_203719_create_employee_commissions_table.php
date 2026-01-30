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
        Schema::create('employee_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unsignedBigInteger('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unsignedBigInteger('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->decimal('commission_percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'product_id', 'employee_id'], 'unique_product_employee');
            $table->index('product_id');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_commissions');
    }
};
