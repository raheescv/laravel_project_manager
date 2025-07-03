<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'round_off')) {
                $table->decimal('round_off', 10, 2)->default(0)->after('freight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            if (Schema::hasColumn('sales', 'round_off')) {
                $table->dropColumn('round_off');
            }
        });
    }
};
