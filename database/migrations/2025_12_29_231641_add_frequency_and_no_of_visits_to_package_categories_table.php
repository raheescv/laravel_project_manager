<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('package_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('package_categories', 'frequency')) {
                $table->string('frequency')->nullable()->after('price')->default('daily');
            }
            if (! Schema::hasColumn('package_categories', 'no_of_visits')) {
                $table->integer('no_of_visits')->nullable()->after('frequency')->default(1);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_categories', function (Blueprint $table) {
            if (Schema::hasColumn('package_categories', 'frequency')) {
                $table->dropColumn('frequency');
            }
            if (Schema::hasColumn('package_categories', 'no_of_visits')) {
                $table->dropColumn('no_of_visits');
            }
        });
    }
};
