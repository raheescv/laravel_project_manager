<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * A deposit recorded without a payment method must not fall back to the
     * legacy "cash" default.
     */
    public function up(): void
    {
        Schema::table('rent_out_securities', function (Blueprint $table) {
            $table->string('payment_mode')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('rent_out_securities', function (Blueprint $table) {
            $table->string('payment_mode')->default('cash')->change();
        });
    }
};
