<?php

use App\Enums\Purchase\PurchaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'local_purchase_order_id')) {
                $table->unsignedBigInteger('local_purchase_order_id')->nullable()->after('account_id');
                $table->index('local_purchase_order_id');
                $table->foreign('local_purchase_order_id')->references('id')->on('local_purchase_orders')->onDelete('restrict');
            }

            if (! Schema::hasColumn('purchases', 'decision_by')) {
                $table->unsignedBigInteger('decision_by')->nullable()->after('status');
                $table->index('decision_by');
            }

            if (! Schema::hasColumn('purchases', 'decision_at')) {
                $table->datetime('decision_at')->nullable()->after('decision_by');
            }

            if (! Schema::hasColumn('purchases', 'decision_note')) {
                $table->text('decision_note')->nullable()->after('decision_at');
            }
        });

        $statuses = implode("','", array_keys(PurchaseStatus::values()));
        DB::statement("ALTER TABLE purchases MODIFY COLUMN `status` ENUM('{$statuses}') DEFAULT 'completed'");
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'local_purchase_order_id')) {
                $table->dropForeign(['local_purchase_order_id']);
                $table->dropIndex(['local_purchase_order_id']);
                $table->dropColumn('local_purchase_order_id');
            }

            if (Schema::hasColumn('purchases', 'decision_by')) {
                $table->dropIndex(['decision_by']);
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
