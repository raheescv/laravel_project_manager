<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Add composite indexes to all Rentout module tables to ensure
     * every filtered/sorted column in Livewire queries hits an index.
     */
    public function up(): void
    {
        Schema::table('rent_outs', function (Blueprint $table) {
            $table->index(['tenant_id', 'agreement_type', 'start_date'], 'rent_outs_t_at_start_idx');
            $table->index(['tenant_id', 'agreement_type', 'end_date'], 'rent_outs_t_at_end_idx');
            $table->index(['tenant_id', 'property_building_id'], 'rent_outs_t_building_idx');
            $table->index(['tenant_id', 'property_group_id'], 'rent_outs_t_group_idx');
            $table->index(['tenant_id', 'property_type_id'], 'rent_outs_t_type_idx');
            $table->index(['tenant_id', 'salesman_id'], 'rent_outs_t_salesman_idx');
        });

        Schema::table('rent_out_securities', function (Blueprint $table) {
            $table->index(['tenant_id', 'status', 'due_date'], 'ro_sec_t_status_due_idx');
            $table->index(['tenant_id', 'type'], 'ro_sec_t_type_idx');
            $table->index(['tenant_id', 'payment_mode'], 'ro_sec_t_pmode_idx');
        });

        Schema::table('rent_out_cheques', function (Blueprint $table) {
            $table->index(['tenant_id', 'date'], 'ro_cheques_t_date_idx');
        });

        Schema::table('rent_out_payment_terms', function (Blueprint $table) {
            $table->index(['tenant_id', 'status', 'due_date'], 'ro_pt_t_status_due_idx');
            $table->index(['tenant_id', 'payment_mode'], 'ro_pt_t_pmode_idx');
        });

        Schema::table('rent_out_utility_terms', function (Blueprint $table) {
            $table->index(['tenant_id', 'date'], 'ro_ut_t_date_idx');
        });

        Schema::table('rent_out_services', function (Blueprint $table) {
            $table->index(['tenant_id', 'start_date'], 'ro_services_t_start_idx');
        });

        Schema::table('rent_out_transactions', function (Blueprint $table) {
            $table->index(['tenant_id', 'category'], 'ro_txn_t_category_idx');
            $table->index(['tenant_id', 'group'], 'ro_txn_t_group_idx');
            $table->index(['tenant_id', 'payment_type'], 'ro_txn_t_ptype_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rent_outs', function (Blueprint $table) {
            $table->dropIndex('rent_outs_t_at_start_idx');
            $table->dropIndex('rent_outs_t_at_end_idx');
            $table->dropIndex('rent_outs_t_building_idx');
            $table->dropIndex('rent_outs_t_group_idx');
            $table->dropIndex('rent_outs_t_type_idx');
            $table->dropIndex('rent_outs_t_salesman_idx');
        });

        Schema::table('rent_out_securities', function (Blueprint $table) {
            $table->dropIndex('ro_sec_t_status_due_idx');
            $table->dropIndex('ro_sec_t_type_idx');
            $table->dropIndex('ro_sec_t_pmode_idx');
        });

        Schema::table('rent_out_cheques', function (Blueprint $table) {
            $table->dropIndex('ro_cheques_t_date_idx');
        });

        Schema::table('rent_out_payment_terms', function (Blueprint $table) {
            $table->dropIndex('ro_pt_t_status_due_idx');
            $table->dropIndex('ro_pt_t_pmode_idx');
        });

        Schema::table('rent_out_utility_terms', function (Blueprint $table) {
            $table->dropIndex('ro_ut_t_date_idx');
        });

        Schema::table('rent_out_services', function (Blueprint $table) {
            $table->dropIndex('ro_services_t_start_idx');
        });

        Schema::table('rent_out_transactions', function (Blueprint $table) {
            $table->dropIndex('ro_txn_t_category_idx');
            $table->dropIndex('ro_txn_t_group_idx');
            $table->dropIndex('ro_txn_t_ptype_idx');
        });
    }
};
