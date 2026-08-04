<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('rent_outs', function (Blueprint $table) {
            $table->date('inspection_date')->nullable()->after('vacate_date');
        });
    }

    public function down(): void
    {
        Schema::table('rent_outs', function (Blueprint $table) {
            $table->dropColumn('inspection_date');
        });
    }
};
