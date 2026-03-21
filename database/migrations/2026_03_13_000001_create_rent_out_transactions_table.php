<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('rent_out_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('rent_out_id')->constrained('rent_outs')->cascadeOnDelete();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('debit', 15, 2)->default(0);
            $table->unsignedBigInteger('account_id');
            $table->string('source'); // PaymentTerm, UtilityTerm, Service, ServiceCharge, Payout
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('model', 50)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('group')->nullable();
            $table->string('category')->nullable();
            $table->string('payment_type')->nullable();
            $table->text('remark')->nullable();
            $table->string('reason')->nullable();
            $table->string('voucher_no')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'rent_out_id']);
            $table->index(['tenant_id', 'source']);
            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'model', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_out_transactions');
    }
};
