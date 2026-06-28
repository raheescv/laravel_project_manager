<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('local_purchase_orders', function (Blueprint $table) {
            $table->json('payment_terms')->nullable()->after('confirmation_note');
        });
    }

    public function down(): void
    {
        Schema::table('local_purchase_orders', function (Blueprint $table) {
            $table->dropColumn('payment_terms');
        });
    }
};
