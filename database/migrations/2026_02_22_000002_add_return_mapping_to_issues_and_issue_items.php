<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('issues', 'source_issue_id')) {
            Schema::table('issues', function (Blueprint $table) {
                $table->unsignedBigInteger('source_issue_id')->nullable()->after('account_id');
                $table->foreign('source_issue_id')->references('id')->on('issues')->nullOnDelete();
                $table->index('source_issue_id');
            });
        }

        if (! Schema::hasColumn('issue_items', 'source_issue_item_id')) {
            Schema::table('issue_items', function (Blueprint $table) {
                $table->unsignedBigInteger('source_issue_item_id')->nullable()->after('inventory_id');
                $table->unsignedInteger('source_item_order')->nullable()->after('source_issue_item_id');
                $table->foreign('source_issue_item_id')->references('id')->on('issue_items')->nullOnDelete();
                $table->index('source_issue_item_id');
                $table->index('source_item_order');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('issue_items', 'source_issue_item_id')) {
            Schema::table('issue_items', function (Blueprint $table) {
                $table->dropForeign(['source_issue_item_id']);
                $table->dropIndex(['source_issue_item_id']);
                $table->dropIndex(['source_item_order']);
                $table->dropColumn(['source_issue_item_id', 'source_item_order']);
            });
        }

        if (Schema::hasColumn('issues', 'source_issue_id')) {
            Schema::table('issues', function (Blueprint $table) {
                $table->dropForeign(['source_issue_id']);
                $table->dropIndex(['source_issue_id']);
                $table->dropColumn('source_issue_id');
            });
        }
    }
};
