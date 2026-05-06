<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'asset_account_id')) {
                $table->unsignedBigInteger('asset_account_id')->nullable()->after('expense_account_id');
                $table->foreign('asset_account_id')->references('id')->on('accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'accumulated_depreciation_account_id')) {
                $table->unsignedBigInteger('accumulated_depreciation_account_id')->nullable()->after('asset_account_id');
                $table->foreign('accumulated_depreciation_account_id')->references('id')->on('accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'depreciation_expense_account_id')) {
                $table->unsignedBigInteger('depreciation_expense_account_id')->nullable()->after('accumulated_depreciation_account_id');
                $table->foreign('depreciation_expense_account_id')->references('id')->on('accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'disposed_at')) {
                $table->timestamp('disposed_at')->nullable()->after('prorata_date');
            }
            if (! Schema::hasColumn('products', 'disposed_value')) {
                $table->decimal('disposed_value', 12, 2)->nullable()->after('disposed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'depreciation_expense_account_id')) {
                $table->dropForeign(['depreciation_expense_account_id']);
                $table->dropColumn('depreciation_expense_account_id');
            }
            if (Schema::hasColumn('products', 'accumulated_depreciation_account_id')) {
                $table->dropForeign(['accumulated_depreciation_account_id']);
                $table->dropColumn('accumulated_depreciation_account_id');
            }
            if (Schema::hasColumn('products', 'asset_account_id')) {
                $table->dropForeign(['asset_account_id']);
                $table->dropColumn('asset_account_id');
            }
            if (Schema::hasColumn('products', 'disposed_value')) {
                $table->dropColumn('disposed_value');
            }
            if (Schema::hasColumn('products', 'disposed_at')) {
                $table->dropColumn('disposed_at');
            }
        });
    }
};
