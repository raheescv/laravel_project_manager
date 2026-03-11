<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rent_out_payment_terms', function (Blueprint $table) {
            $table->string('label')->nullable()->after('rent_out_id');
            $table->decimal('paid', 16, 2)->default(0)->after('total');
            $table->decimal('balance', 16, 2)->default(0)->after('paid');
            $table->string('payment_mode')->nullable()->after('status');
            $table->string('cheque_no')->nullable()->after('payment_mode');
        });
    }

    public function down(): void
    {
        Schema::table('rent_out_payment_terms', function (Blueprint $table) {
            $table->dropColumn(['label', 'paid', 'balance', 'payment_mode', 'cheque_no']);
        });
    }
};
