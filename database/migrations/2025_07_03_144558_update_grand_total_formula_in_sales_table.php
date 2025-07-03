<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('grand_total', 16, 2)->storedAs('total - other_discount + freight + round_off')->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('grand_total', 16, 2)->storedAs('total - other_discount + freight + round_off')->change();
        });
    }
};
