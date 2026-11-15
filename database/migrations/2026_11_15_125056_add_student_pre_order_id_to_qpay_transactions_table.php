<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A bank card payment made for one canteen pre-order: once it lands on the card,
 * that order is paid with it (QPay\SettleAction), whatever the card owed before.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('qpay_transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('student_pre_order_id')->nullable()->after('guardian_id');
            $table->index('student_pre_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('qpay_transactions', function (Blueprint $table): void {
            $table->dropIndex(['student_pre_order_id']);
            $table->dropColumn('student_pre_order_id');
        });
    }
};
