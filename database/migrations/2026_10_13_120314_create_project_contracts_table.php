<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('project_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('branch_id');

            $table->string('reference_no', 30);
            $table->string('project_name');
            $table->unsignedBigInteger('account_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('no_of_terms')->default(1);
            $table->string('payment_frequency', 20);
            $table->decimal('contract_amount', 16, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->text('remark')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_no'], 'pc_tenant_reference_unique');
            $table->index(['tenant_id', 'branch_id', 'status'], 'pc_tenant_branch_status_index');
            $table->index(['tenant_id', 'account_id'], 'pc_tenant_account_index');
            $table->index(['tenant_id', 'end_date'], 'pc_tenant_end_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contracts');
    }
};
