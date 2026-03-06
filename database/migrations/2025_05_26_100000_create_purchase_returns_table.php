<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->string('invoice_no')->nullable();
            $table->unsignedBigInteger('branch_id')->references('id')->on('branches');
            $table->unsignedBigInteger('account_id')->references('id')->on('accounts');
            $table->date('date');

            $table->decimal('gross_amount', 16, 2)->default(0);
            $table->decimal('item_discount', 16, 2)->default(0);
            $table->decimal('tax_amount', 16, 2)->default(0);

            $table->decimal('total', 16, 2)->default(0);

            $table->decimal('other_discount', 16, 2)->default(0);
            $table->decimal('freight', 16, 2)->default(0);

            $table->decimal('grand_total', 16, 2)->storedAs('total - other_discount');
            $table->decimal('paid', 16, 2)->default(0);
            $table->decimal('balance', 16, 2)->storedAs('grand_total - paid');

            $table->enum('status', array_keys(purchaseReturnStatuses()))->default('completed');
            $table->text('reason')->nullable();

            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('cancelled_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');

            $table->softDeletes();
            $table->timestamps();

            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
