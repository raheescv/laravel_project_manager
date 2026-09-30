<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Tap refund of a captured storefront charge (Sale → Online Payments → Refund).
 *
 * Tap answers a refund with its own id and a status that may still be PENDING;
 * the id lets a later check or Tap's webhook find the row. What was sent to Tap
 * is kept beside what it answered, for the charge and the refund alike, so staff
 * can see the whole exchange from the Online Payments details popup.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_checkouts', function (Blueprint $table) {
            $table->json('gateway_request')->nullable()->after('gateway_status');
            $table->string('refund_id', 64)->nullable()->after('paid_at');
            $table->string('refund_status', 30)->nullable()->after('refund_id');
            $table->decimal('refund_amount', 16, 2)->nullable()->after('refund_status');
            $table->string('refund_reason', 255)->nullable()->after('refund_amount');
            $table->json('refund_request')->nullable()->after('refund_reason');
            $table->json('refund_response')->nullable()->after('refund_request');
            $table->unsignedBigInteger('refund_requested_by')->nullable()->after('refund_response');
            $table->foreign('refund_requested_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('refund_requested_at')->nullable()->after('refund_requested_by');
            $table->timestamp('refunded_at')->nullable()->after('refund_requested_at');

            $table->index('refund_id', 'storefront_checkouts_refund_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_checkouts', function (Blueprint $table) {
            $table->dropForeign(['refund_requested_by']);
            $table->dropIndex('storefront_checkouts_refund_id_index');
            $table->dropColumn(['gateway_request', 'refund_id', 'refund_status', 'refund_amount', 'refund_reason', 'refund_request', 'refund_response', 'refund_requested_by', 'refund_requested_at', 'refunded_at']);
        });
    }
};
