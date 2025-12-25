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
        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'opening_debit')) {
                $table->decimal('opening_debit', 10, 2)->default(0)->after('description');
            }
            if (! Schema::hasColumn('accounts', 'opening_credit')) {
                $table->decimal('opening_credit', 10, 2)->default(0)->after('opening_debit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'opening_debit')) {
                $table->dropColumn('opening_debit');
            }
            if (Schema::hasColumn('accounts', 'opening_credit')) {
                $table->dropColumn('opening_credit');
            }
        });
    }
};
