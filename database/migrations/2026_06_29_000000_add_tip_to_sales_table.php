<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Gratuity collected on the sale. Stored as an independent extra
            // amount — intentionally NOT part of the generated grand_total.
            $table->decimal('tip', 16, 2)->default(0)->after('freight');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('tip');
        });
    }
};
