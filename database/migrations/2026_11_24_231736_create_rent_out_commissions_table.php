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
        Schema::create('rent_out_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('rent_out_id')->constrained('rent_outs')->cascadeOnDelete();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->date('date');
            $table->string('commission_type', 20)->default('percentage');
            $table->decimal('commission_percentage', 8, 2)->default(0);
            $table->decimal('contract_amount', 16, 2)->default(0);
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->string('invoice_no')->nullable();
            $table->string('remark')->nullable();

            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('ceo_approved_by')->nullable();
            $table->timestamp('ceo_approved_at')->nullable();
            $table->unsignedBigInteger('owner_approved_by')->nullable();
            $table->timestamp('owner_approved_at')->nullable();
            $table->unsignedBigInteger('finance_approved_by')->nullable();
            $table->timestamp('finance_approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();

            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('payment_account_id')->nullable();
            $table->date('paid_date')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->unsignedBigInteger('payment_journal_id')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rent_out_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'agent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_out_commissions');
    }
};
