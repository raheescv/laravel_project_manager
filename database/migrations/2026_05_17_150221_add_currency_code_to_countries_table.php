<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('currency_code', 10)->nullable()->after('phone_code');
            $table->string('currency_symbol', 10)->nullable()->after('currency_code');
        });

        DB::table('countries')->where('code', 'IN')->update([
            'currency_code' => 'RS',
            'currency_symbol' => '₹',
        ]);
        DB::table('countries')->where('code', 'QA')->update([
            'currency_code' => 'QAR',
            'currency_symbol' => 'ر.ق',
        ]);
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'currency_symbol']);
        });
    }
};
