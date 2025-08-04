<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sale_day_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_day_sessions', 'sync_amount')) {
                $table->decimal('sync_amount', 10, 2)->default(0)->after('closing_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_day_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sale_day_sessions', 'sync_amount')) {
                $table->dropColumn('sync_amount');
            }
        });
    }
};
