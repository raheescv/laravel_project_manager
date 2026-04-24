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
        Schema::table('tailoring_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('tailoring_orders', 'sale_day_session_id')) {
                $table->unsignedBigInteger('sale_day_session_id')->nullable();
                $table->foreign('sale_day_session_id')->references('id')->on('sale_day_sessions')->onDelete('set null');
                $table->index(['tenant_id', 'sale_day_session_id'], 'tailoring_orders_tenant_session_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tailoring_orders', function (Blueprint $table) {
            if (Schema::hasColumn('tailoring_orders', 'sale_day_session_id')) {
                $table->dropForeign(['sale_day_session_id']);
                $table->dropIndex(['tenant_id', 'sale_day_session_id']);
                $table->dropColumn('sale_day_session_id');
            }
        });
    }
};
