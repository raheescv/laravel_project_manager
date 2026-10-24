<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The biller's own running bill number for the "Office Bill" receipt:
 * `{users.code}/{nth sale that biller rang up on that sale date}`. The
 * existing (tenant_id, created_by, date) index serves the next-number lookup.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedInteger('staff_bill_sequence')->nullable()->after('invoice_no');
            $table->string('bill_no')->nullable()->after('staff_bill_sequence');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['staff_bill_sequence', 'bill_no']);
        });
    }
};
