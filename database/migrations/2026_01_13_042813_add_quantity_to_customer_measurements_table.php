<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_measurements', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_measurements', 'quantity')) {
                $table->integer('quantity')->nullable()->after('value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_measurements', function (Blueprint $table) {
             if (Schema::hasColumn('customer_measurements', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};
