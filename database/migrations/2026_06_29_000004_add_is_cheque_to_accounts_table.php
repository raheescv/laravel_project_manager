<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Marks a payment-method account as a cheque account so flows can
            // prompt for cheque details (bank name, cheque no) when it is used.
            $table->boolean('is_cheque')->default(0)->after('is_locked');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('is_cheque');
        });
    }
};
