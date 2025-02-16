<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (! Schema::hasColumn('journals', 'remarks')) {
                $table->string('remarks')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (Schema::hasColumn('journals', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });
    }
};
