<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('rent_out_securities', function (Blueprint $table) {
            // Concrete payment-method account (Cash / Bank / Card ...) the deposit
            // is received into / refunded from. Drives the journal + payment entries.
            $table->unsignedBigInteger('account_id')->nullable()->after('amount');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::table('rent_out_securities', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
