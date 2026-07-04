<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('rent_out_securities', function (Blueprint $table) {
            $table->date('collected_date')->nullable()->after('due_date');
            $table->date('returned_date')->nullable()->after('collected_date');
        });
    }

    public function down(): void
    {
        Schema::table('rent_out_securities', function (Blueprint $table) {
            $table->dropColumn(['collected_date', 'returned_date']);
        });
    }
};
