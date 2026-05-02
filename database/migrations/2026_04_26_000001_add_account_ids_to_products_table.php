<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'income_account_id')) {
                $table->unsignedBigInteger('income_account_id')->nullable()->after('cost');
                $table->foreign('income_account_id')->references('id')->on('accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'expense_account_id')) {
                $table->unsignedBigInteger('expense_account_id')->nullable()->after('income_account_id');
                $table->foreign('expense_account_id')->references('id')->on('accounts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'income_account_id')) {
                $table->dropForeign(['income_account_id']);
                $table->dropColumn('income_account_id');
            }
            if (Schema::hasColumn('products', 'expense_account_id')) {
                $table->dropForeign(['expense_account_id']);
                $table->dropColumn('expense_account_id');
            }
        });
    }
};
