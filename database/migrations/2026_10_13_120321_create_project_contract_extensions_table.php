<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('project_contract_extensions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('project_contract_id')->constrained('project_contracts')->cascadeOnDelete();

            $table->date('old_end_date');
            $table->date('new_end_date');
            $table->decimal('old_amount', 16, 2);
            $table->decimal('additional_amount', 16, 2)->default(0);
            $table->unsignedSmallInteger('old_no_of_terms');
            $table->unsignedSmallInteger('additional_terms')->default(0);
            $table->text('reason');
            $table->string('document_path')->nullable();
            $table->string('status', 30)->default('pending_approval');
            $table->timestamp('applied_at')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['tenant_id', 'project_contract_id'], 'pc_extensions_contract_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contract_extensions');
    }
};
