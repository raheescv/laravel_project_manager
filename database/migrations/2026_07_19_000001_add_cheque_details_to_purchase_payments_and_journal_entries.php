<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->string('cheque_no')->nullable()->after('amount');
            $table->string('bank_name')->nullable()->after('cheque_no');
            $table->date('cheque_date')->nullable()->after('bank_name');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('cheque_no')->nullable()->after('reference_number');
            $table->string('bank_name')->nullable()->after('cheque_no');
            $table->date('cheque_date')->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropColumn(['cheque_no', 'bank_name', 'cheque_date']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn(['cheque_no', 'bank_name', 'cheque_date']);
        });
    }
};
