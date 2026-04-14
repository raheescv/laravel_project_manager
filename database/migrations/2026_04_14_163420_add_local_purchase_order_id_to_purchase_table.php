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
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'local_purchase_order_id')) {
                $table->unsignedBigInteger('local_purchase_order_id')->nullable()->after('account_id');
            }
            if (! Schema::hasColumn('purchases', 'decision_by')) {
                $table->unsignedBigInteger('decision_by')->nullable()->after('status');
            }
            if (! Schema::hasColumn('purchases', 'decision_at')) {
                $table->datetime('decision_at')->nullable()->after('decision_by');
            }
            if (! Schema::hasColumn('purchases', 'decision_note')) {
                $table->text('decision_note')->nullable()->after('decision_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'local_purchase_order_id')) {
                $table->dropColumn('local_purchase_order_id');
            }
            if (Schema::hasColumn('purchases', 'decision_by')) {
                $table->dropColumn('decision_by');
            }
            if (Schema::hasColumn('purchases', 'decision_at')) {
                $table->dropColumn('decision_at');
            }
            if (Schema::hasColumn('purchases', 'decision_note')) {
                $table->dropColumn('decision_note');
            }
        });
    }
};
