<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (! Schema::hasColumn('journals', 'person_name')) {
                $table->string('person_name')->nullable()->after('reference_number');
            }
            if (! Schema::hasColumn('journals', 'source')) {
                $table->string('source')->nullable()->after('person_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (Schema::hasColumn('journals', 'person_name')) {
                $table->dropColumn('person_name');
            }
            if (Schema::hasColumn('journals', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
