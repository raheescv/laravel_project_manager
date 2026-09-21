<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online top-ups can now be paid two ways, and the parent picks one:
 * a Qatar debit card through QPay, or a credit card through the Mastercard
 * Gateway (MPGS Hosted Checkout). Both land in this one table — same PUN, same
 * broken-transaction chase, same journal — told apart by `gateway`.
 *
 * `card_brand` / `funding_method` are what the card gateway reports about the
 * card actually used (VISA / MASTERCARD, CREDIT / DEBIT / CHARGE); QPay rows leave them empty.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('qpay_transactions', function (Blueprint $table) {
            $table->string('gateway', 10)->default('qpay')->after('type');
            $table->string('card_brand', 20)->nullable()->after('masked_card');
            $table->string('funding_method', 10)->nullable()->after('card_brand');
        });
    }

    public function down(): void
    {
        Schema::table('qpay_transactions', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'card_brand', 'funding_method']);
        });
    }
};
