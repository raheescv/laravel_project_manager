<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('journal_id')->references('id')->on('journals');
            $table->unsignedBigInteger('branch_id')->references('id')->on('branches');
            $table->unsignedBigInteger('account_id')->references('id')->on('accounts');
            $table->unsignedBigInteger('counter_account_id')->references('id')->on('accounts');

            $table->date('date');
            $table->date('delivered_date')->nullable();

            $table->decimal('debit', 16, 2)->default(0);
            $table->decimal('credit', 16, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->string('source')->nullable();
            $table->string('person_name')->nullable();
            $table->string('description')->nullable();
            $table->string('journal_remarks')->nullable();
            $table->string('reference_number')->nullable();

            $table->string('journal_model', 50)->nullable();
            $table->unsignedBigInteger('journal_model_id')->nullable();

            $table->string('model', 50)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();

            $table->index(['tenant_id'], 'journal_entry_tenant_id_index');
            $table->index(['tenant_id', 'date', 'branch_id'], 'journal_entry_tenant_date_branch_id_index');
            $table->index(['counter_account_id'], 'counter_account_id_index');
            $table->index(['account_id', 'counter_account_id', 'journal_id'], 'journal_entry_account_id_counter_account_id_journal_id_index');

            $table->index(['tenant_id', 'account_id', 'credit'], 'journal_entry_tenant_account_id_credit_index');
            $table->index(['tenant_id', 'account_id', 'debit'], 'journal_entry_tenant_account_id_debit_index');

            $table->index(['tenant_id', 'account_id', 'date', 'credit'], 'journal_entry_tenant_account_id_date_credit_index');
            $table->index(['tenant_id', 'account_id', 'date', 'debit'], 'journal_entry_tenant_account_id_date_debit_index');

            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
