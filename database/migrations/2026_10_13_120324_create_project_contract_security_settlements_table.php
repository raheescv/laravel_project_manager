<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('project_contract_security_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id', 'pc_settlement_tenant_foreign')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('project_contract_security_id');
            $table->foreign('project_contract_security_id', 'pc_settlement_security_foreign')->references('id')->on('project_contract_securities')->cascadeOnDelete();

            $table->string('type', 20);
            $table->decimal('amount', 16, 2);
            $table->date('date');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('pending_approval');
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->timestamp('applied_at')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['tenant_id', 'project_contract_security_id'], 'pc_settlement_security_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contract_security_settlements');
    }
};
