<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('tailoring_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('tailoring_orders', 'cutting_slip_printed_at')) {
                $table->timestamp('cutting_slip_printed_at')->nullable()->after('completion_date');
                $table->index(['tenant_id', 'cutting_slip_printed_at'], 'tailoring_orders_tenant_cutting_slip_printed_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tailoring_orders', function (Blueprint $table): void {
            if (Schema::hasColumn('tailoring_orders', 'cutting_slip_printed_at')) {
                $table->dropIndex('tailoring_orders_tenant_cutting_slip_printed_idx');
                $table->dropColumn('cutting_slip_printed_at');
            }
        });
    }
};
