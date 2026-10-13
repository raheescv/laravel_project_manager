<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('project_contract_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id', 'pc_alloc_tenant_foreign')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('project_contract_payment_id');
            $table->foreign('project_contract_payment_id', 'pc_alloc_payment_foreign')->references('id')->on('project_contract_payments')->cascadeOnDelete();
            $table->unsignedBigInteger('project_contract_payment_term_id');
            $table->foreign('project_contract_payment_term_id', 'pc_alloc_term_foreign')->references('id')->on('project_contract_payment_terms')->cascadeOnDelete();
            $table->decimal('amount', 16, 2);
            $table->timestamps();

            $table->index(['tenant_id', 'project_contract_payment_term_id'], 'pc_alloc_term_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contract_payment_allocations');
    }
};
