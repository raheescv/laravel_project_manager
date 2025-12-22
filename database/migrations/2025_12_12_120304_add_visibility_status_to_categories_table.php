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
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'sale_visibility_flag')) {
                $table->boolean('sale_visibility_flag')->default(true)->after('name');
            }
            if (! Schema::hasColumn('categories', 'online_visibility_flag')) {
                $table->boolean('online_visibility_flag')->default(true)->after('sale_visibility_flag');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'sale_visibility_flag')) {
                $table->dropColumn('sale_visibility_flag');
            }
            if (Schema::hasColumn('categories', 'online_visibility_flag')) {
                $table->dropColumn('online_visibility_flag');
            }
        });
    }
};
