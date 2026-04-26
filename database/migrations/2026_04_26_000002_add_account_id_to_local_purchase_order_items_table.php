<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('local_purchase_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('local_purchase_order_items', 'account_id')) {
                $table->unsignedBigInteger('account_id')->nullable()->after('rate');
                $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('local_purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('local_purchase_order_items', 'account_id')) {
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            }
        });
    }
};
