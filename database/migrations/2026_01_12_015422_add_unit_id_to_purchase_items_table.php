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
        Schema::table('purchase_items', function (Blueprint $table) {
            if (! $table->hasColumn('unit_id')) {
                $table->foreignId('unit_id')->nullable()->constrained('units')->after('product_id');
            }
            if (! $table->hasColumn('conversion_factor')) {
                $table->decimal('conversion_factor', 16, 2)->default(1)->after('unit_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if ($table->hasColumn('unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }
            if ($table->hasColumn('conversion_factor')) {
                $table->dropColumn('conversion_factor');
            }
        });
    }
};
