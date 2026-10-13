<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('project_contract_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('project_contract_id')->constrained('project_contracts')->cascadeOnDelete();

            $table->string('voucher_no', 30);
            $table->date('date');
            $table->decimal('amount', 16, 2);
            $table->unsignedBigInteger('payment_method_id');
            $table->string('cheque_no', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('reference', 150)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('posted');

            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('reversal_journal_id')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->string('reversal_reason')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'voucher_no'], 'pc_payments_voucher_unique');
            $table->index(['tenant_id', 'project_contract_id'], 'pc_payments_contract_index');
            $table->index(['tenant_id', 'status', 'date'], 'pc_payments_status_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contract_payments');
    }
};
