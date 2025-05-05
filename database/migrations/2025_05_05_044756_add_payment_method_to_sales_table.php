<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'payment_method_ids')) {
                $table->string('payment_method_ids')->nullable()->after('balance');
            }
            if (! Schema::hasColumn('sales', 'payment_method_name')) {
                $table->string('payment_method_name')->nullable()->after('payment_method_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'payment_method_ids')) {
                $table->dropColumn('payment_method_ids');
            }
            if (Schema::hasColumn('sales', 'payment_method_name')) {
                $table->dropColumn('payment_method_name');
            }
        });
    }
};
