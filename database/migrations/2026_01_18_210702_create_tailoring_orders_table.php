<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('tailoring_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->string('order_no');
            $table->unique(['tenant_id', 'order_no']);
            $table->unsignedBigInteger('branch_id')->nullable()->references('id')->on('branches');
            $table->unsignedBigInteger('account_id')->nullable()->references('id')->on('accounts');
            $table->string('customer_name')->nullable();
            $table->string('customer_mobile', 15)->nullable();
            $table->unsignedBigInteger('salesman_id')->nullable()->references('id')->on('users');
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->decimal('gross_amount', 16, 2)->default(0);
            $table->decimal('item_discount', 16, 2)->default(0);
            $table->decimal('tax_amount', 16, 2)->default(0);
            $table->decimal('total', 16, 2)->default(0);
            $table->decimal('other_discount', 16, 2)->default(0);
            $table->decimal('freight', 16, 2)->default(0);
            $table->decimal('round_off', 10, 2)->default(0);
            $table->decimal('grand_total', 16, 2)->default(0);
            $table->decimal('paid', 16, 2)->default(0);
            $table->decimal('balance', 16, 2)->default(0);
            $table->string('payment_method_ids')->nullable();
            $table->string('payment_method_name')->nullable();
            $table->enum('status', ['draft', 'pending', 'confirmed', 'in_progress', 'completed', 'delivered', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['tenant_id', 'order_no']);
            $table->index(['tenant_id', 'order_date']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailoring_orders');
    }
};
