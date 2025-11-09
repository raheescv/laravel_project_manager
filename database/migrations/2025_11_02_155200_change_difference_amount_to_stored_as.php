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
        Schema::table('sale_day_sessions', function (Blueprint $table) {
            // Drop the existing column if it exists
            if (Schema::hasColumn('sale_day_sessions', 'difference_amount')) {
                $table->dropColumn('difference_amount');
            }

            // Recreate it as a stored generated column
            $table->decimal('difference_amount', 16, 2)->after('expected_amount')->storedAs('(closing_amount - expected_amount)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_day_sessions', function (Blueprint $table) {
            // Drop the generated column
            if (Schema::hasColumn('sale_day_sessions', 'difference_amount')) {
                $table->dropColumn('difference_amount');
            }

            // Recreate it as a regular column without storedAs
            $table->decimal('difference_amount', 16, 2)->nullable();
        });
    }
};
