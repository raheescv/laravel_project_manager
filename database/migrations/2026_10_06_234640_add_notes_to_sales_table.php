<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Free-text note the cashier attaches at checkout (POS confirm modal,
            // mobile Review & Pay) — e.g. "deliver after 6pm", "gift wrap".
            $table->text('notes')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
