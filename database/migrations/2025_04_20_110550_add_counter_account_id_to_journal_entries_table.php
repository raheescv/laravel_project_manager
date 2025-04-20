<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('journal_entries', 'counter_account_id')) {
                $table->unsignedBigInteger('counter_account_id')->references('id')->on('accounts')->after('account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (Schema::hasColumn('journal_entries', 'counter_account_id')) {
                $table->dropColumn('counter_account_id');
            }
        });
    }
};
