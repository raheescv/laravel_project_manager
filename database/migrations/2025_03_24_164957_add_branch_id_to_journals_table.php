<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (! Schema::hasColumn('journals', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->references('id')->on('branches')->default(1)->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (Schema::hasColumn('journals', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
        });
    }
};
