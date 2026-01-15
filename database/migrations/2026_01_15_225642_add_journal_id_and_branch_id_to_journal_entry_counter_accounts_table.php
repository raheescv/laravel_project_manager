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
        Schema::table('journal_entry_counter_accounts', function (Blueprint $table) {
            // add if condition if not exists
            if (! Schema::hasColumn('journal_entry_counter_accounts', 'journal_id')) {
                $table->unsignedBigInteger('journal_id')->nullable()->after('tenant_id');
                $table->foreign('journal_id')->references('id')->on('journals')->onDelete('cascade');
                $table->index(['journal_id'], 'j_e_c_a_journal_id_index');
            }
            if (! Schema::hasColumn('journal_entry_counter_accounts', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('journal_id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                $table->index(['branch_id'], 'j_e_c_a_branch_id_index');
            }
            if (! Schema::hasIndex('journal_entry_counter_accounts', 'j_e_c_a_tenant_journal_index')) {
                $table->index(['tenant_id', 'journal_id'], 'j_e_c_a_tenant_journal_index');
            }
            if (! Schema::hasIndex('journal_entry_counter_accounts', 'j_e_c_a_tenant_branch_index')) {
                $table->index(['tenant_id', 'branch_id'], 'j_e_c_a_tenant_branch_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entry_counter_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('journal_entry_counter_accounts', 'journal_id')) {
                $table->dropForeign(['journal_id']);
                $table->dropIndex('j_e_c_a_journal_id_index');
                $table->dropColumn('journal_id');
            }
            if (Schema::hasColumn('journal_entry_counter_accounts', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropIndex('j_e_c_a_branch_id_index');
            }
            if (Schema::hasIndex('journal_entry_counter_accounts', 'j_e_c_a_tenant_journal_index')) {
                $table->dropIndex('j_e_c_a_tenant_journal_index');
            }
            if (Schema::hasIndex('journal_entry_counter_accounts', 'j_e_c_a_tenant_branch_index')) {
                $table->dropIndex('j_e_c_a_tenant_branch_index');
            }
        });
    }
};
