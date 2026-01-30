<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('invoice_no');
            $table->unique(['tenant_id', 'invoice_no']);
            $table->string('reference_no')->nullable();
            $table->unsignedBigInteger('branch_id')->references('id')->on('branches');
            $table->unsignedBigInteger('sale_day_session_id')->nullable();
            $table->foreign('sale_day_session_id')->references('id')->on('sale_day_sessions')->onDelete('set null');
            $table->unsignedBigInteger('account_id')->references('id')->on('accounts');
            $table->date('date');
            $table->date('due_date')->nullable();

            $table->enum('sale_type', array_keys(priceTypes()))->default('normal');

            $table->string('customer_name')->nullable();
            $table->string('customer_mobile', 15)->nullable();

            $table->decimal('gross_amount', 16, 2)->default(0);
            $table->decimal('item_discount', 16, 2)->default(0);
            $table->decimal('tax_amount', 16, 2)->default(0);

            $table->decimal('total', 16, 2)->storedAs('gross_amount - item_discount + tax_amount');

            $table->decimal('other_discount', 16, 2)->default(0);
            $table->decimal('freight', 16, 2)->default(0);
            $table->decimal('round_off', 10, 2)->default(0);

            $table->decimal('grand_total', 16, 2)->storedAs('(total - other_discount + freight) + round_off');
            $table->decimal('paid', 16, 2)->default(0);
            $table->decimal('balance', 16, 2)->storedAs('grand_total - paid');
            $table->string('payment_method_ids')->nullable();
            $table->string('payment_method_name')->nullable();

            $table->text('address')->nullable();
            $table->integer('rating')->nullable();
            $table->enum('feedback_type', array_keys(feedbackTypes()))->nullable();
            $table->text('feedback')->nullable();

            $table->enum('status', array_keys(saleStatuses()))->default('completed');

            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('cancelled_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id'], 'sale_tenant_id_index');
            $table->index(['tenant_id', 'date', 'branch_id'], 'sale_tenant_date_branch_id_index');
            $table->index(['tenant_id', 'branch_id'], 'sale_tenant_branch_id_index');
            $table->index(['tenant_id', 'date', 'branch_id', 'status'], 'sale_tenant_date_branch_id_status_index');

            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
