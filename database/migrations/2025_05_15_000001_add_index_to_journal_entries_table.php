<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up()
    {
        if (Schema::hasTable('journal_entries') && ! Schema::hasIndex('journal_entries', 'counter_account_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->index(['counter_account_id'], 'counter_account_id_index');
            });
        }
        if (Schema::hasTable('journal_entries') && ! Schema::hasIndex('journal_entries', 'account_id_counter_account_id_journal_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->index(['account_id', 'counter_account_id', 'journal_id'], 'account_id_counter_account_id_journal_id_index');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('journal_entries') && Schema::hasIndex('journal_entries', 'counter_account_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex('counter_account_id_index');
            });
        }
        if (Schema::hasTable('journal_entries') && Schema::hasIndex('journal_entries', 'account_id_counter_account_id_journal_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex('account_id_counter_account_id_journal_id_index');
            });
        }
    }
};
