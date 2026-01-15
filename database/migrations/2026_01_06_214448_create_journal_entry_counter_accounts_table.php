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
        Schema::create('journal_entry_counter_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('journal_id');
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('counter_account_id');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('cascade');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('cascade');
            $table->foreign('counter_account_id')->references('id')->on('accounts')->onDelete('cascade');

            $table->index(['tenant_id'], 'j_e_c_a_tenant_id_index');
            $table->index(['branch_id'], 'j_e_c_a_branch_id_index');
            $table->index(['journal_id'], 'j_e_c_a_journal_id_index');
            $table->index(['journal_entry_id'], 'j_e_c_a_journal_entry_id_index');
            $table->index(['counter_account_id'], 'j_e_c_a_counter_account_id_index');
            $table->index(['journal_entry_id', 'counter_account_id'], 'j_e_c_a_entry_account_index');
            $table->index(['tenant_id', 'journal_entry_id'], 'j_e_c_a_tenant_entry_index');
            $table->index(['tenant_id', 'journal_id'], 'j_e_c_a_tenant_journal_index');
            $table->index(['tenant_id', 'branch_id'], 'j_e_c_a_tenant_branch_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_counter_accounts');
    }
};
