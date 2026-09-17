<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every message exchanged with QPay (QCB EZ-Connect) for student card top-ups.
 *
 * This is the gateway's log, not a balance: the card balance is the student
 * account's ledger, and a successful payment reaches it only through the journal
 * in `journal_id`. The row exists because QPay certification requires the
 * merchant to show PUN/amount/status/date on the result page, to re-inquire
 * broken transactions, to drop tampered responses, and to refund.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('qpay_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            $table->string('type', 10)->default('payment');
            // Payment Unique Number: merchant generated, alphanumeric, max 20.
            $table->string('pun', 20)->unique();
            $table->string('original_pun', 20)->nullable();

            // The student's account the money is for.
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->foreign('guardian_id')->references('id')->on('guardians')->nullOnDelete();

            $table->decimal('amount', 16, 2);
            $table->string('currency_code', 3)->default('634');
            $table->string('lang', 2)->default('En');

            $table->string('status', 20)->default('pending');
            $table->string('gateway_status', 30)->nullable();
            $table->text('gateway_status_message')->nullable();
            $table->string('confirmation_id', 40)->nullable();
            $table->string('masked_card', 19)->nullable();
            // Raw ddMMyyyyHHmmss strings as exchanged (Asia/Qatar); they take part in the secure hash.
            $table->string('request_date', 14)->nullable();
            $table->string('response_date', 14)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_inquired_at')->nullable();
            $table->timestamp('tampered_at')->nullable();

            $table->unsignedBigInteger('journal_id')->nullable();
            $table->foreign('journal_id')->references('id')->on('journals')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->text('failure_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'qpay_transactions_tenant_status_index');
            $table->index(['account_id', 'type', 'status'], 'qpay_transactions_account_type_status_index');
            $table->index('original_pun', 'qpay_transactions_original_pun_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qpay_transactions');
    }
};
