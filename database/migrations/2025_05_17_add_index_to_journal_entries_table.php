<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up()
    {
        if (Schema::hasTable('journal_entries') && ! Schema::hasIndex('journal_entries', 'account_id_credit_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->index(['account_id', 'credit'], 'account_id_credit_index');
            });
        }
        if (Schema::hasTable('journal_entries') && ! Schema::hasIndex('journal_entries', 'account_id_debit_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->index(['account_id', 'debit'], 'account_id_debit_index');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('journal_entries') && Schema::hasIndex('journal_entries', 'account_id_credit_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex('account_id_credit_index');
            });
        }
        if (Schema::hasTable('journal_entries') && Schema::hasIndex('journal_entries', 'account_id_debit_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex('account_id_debit_index');
            });
        }
    }
};
