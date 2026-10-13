<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('project_contract_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('project_contract_id')->constrained('project_contracts')->cascadeOnDelete();
            $table->string('approvable_type', 80);
            $table->unsignedBigInteger('approvable_id');

            $table->unsignedSmallInteger('round')->default(1);
            $table->unsignedTinyInteger('level');
            $table->string('approver_role', 80);
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('acted_by')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('submitted_by');

            $table->timestamps();

            $table->index(['tenant_id', 'approvable_type', 'approvable_id'], 'pc_approval_approvable_index');
            $table->index(['tenant_id', 'status'], 'pc_approval_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contract_approvals');
    }
};
