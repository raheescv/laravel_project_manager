<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('project_contract_securities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('project_contract_id')->constrained('project_contracts')->cascadeOnDelete();

            $table->string('direction', 20)->default('received');
            $table->string('form', 20);
            $table->string('purpose', 40);
            $table->decimal('amount', 16, 2);
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->date('cheque_date')->nullable();
            $table->date('received_date')->nullable();
            $table->string('document_path')->nullable();
            $table->unsignedBigInteger('renewed_from_id')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'project_contract_id'], 'pc_securities_contract_index');
            $table->index(['tenant_id', 'valid_until'], 'pc_securities_valid_until_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contract_securities');
    }
};
