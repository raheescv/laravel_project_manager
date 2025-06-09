<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_day_session_id')->nullable()->after('branch_id');
            $table->foreign('sale_day_session_id')->references('id')->on('sale_day_sessions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['sale_day_session_id']);
            $table->dropColumn('sale_day_session_id');
        });
    }
};
