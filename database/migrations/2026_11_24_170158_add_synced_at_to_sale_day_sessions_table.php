<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sale_day_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_day_sessions', 'synced_at')) {
                $table->dateTime('synced_at')->nullable()->after('sync_amount');
            }
            if (! Schema::hasColumn('sale_day_sessions', 'synced_by')) {
                $table->unsignedBigInteger('synced_by')->nullable()->after('synced_at');
                $table->foreign('synced_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_day_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sale_day_sessions', 'synced_by')) {
                $table->dropForeign(['synced_by']);
                $table->dropColumn('synced_by');
            }
            if (Schema::hasColumn('sale_day_sessions', 'synced_at')) {
                $table->dropColumn('synced_at');
            }
        });
    }
};
