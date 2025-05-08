<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        // Drop the computed column by first modifying it to a regular column
        DB::statement('ALTER TABLE sales MODIFY total DECIMAL(16,2)');

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total', 16, 2)->storedAs('gross_amount - item_discount + tax_amount')->change();
        });
    }

    public function down(): void
    {
        // Drop the computed column by first modifying it to a regular column
        DB::statement('ALTER TABLE sales MODIFY total DECIMAL(16,2)');

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total', 16, 2)->storedAs('gross_amount - item_discount + tax_amount')->change();
        });
    }
};
