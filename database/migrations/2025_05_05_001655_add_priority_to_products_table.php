<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'priority')) {
                $table->integer('priority')->default(0)->after('plu');
            }
            if (! Schema::hasColumn('products', 'second_reference_no')) {
                $table->string('second_reference_no')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('products', 'second_reference_no')) {
                $table->dropColumn('second_reference_no');
            }
        });
    }
};
