<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('tailoring_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unsignedBigInteger('tailoring_order_id');
            $table->foreign('tailoring_order_id')->references('id')->on('tailoring_orders')->onDelete('cascade');
            $table->unsignedBigInteger('payment_method_id');
            $table->foreign('payment_method_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->date('date');
            $table->decimal('amount', 16, 2);
            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['tenant_id', 'tailoring_order_id']);
            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'payment_method_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailoring_payments');
    }
};
