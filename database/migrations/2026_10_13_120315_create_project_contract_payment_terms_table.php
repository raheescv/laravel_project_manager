<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('project_contract_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('project_contract_id')->constrained('project_contracts')->cascadeOnDelete();

            $table->string('label');
            $table->date('due_date');
            $table->decimal('amount', 16, 2)->default(0);
            $table->decimal('paid', 16, 2)->default(0);
            $table->decimal('balance', 16, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'project_contract_id'], 'pc_terms_contract_index');
            $table->index(['tenant_id', 'status', 'due_date'], 'pc_terms_status_due_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contract_payment_terms');
    }
};
