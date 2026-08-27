<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Branches that exist for back-office reasons only (warehouse, head office,
     * an online-only ledger) should not be offered to customers by the public
     * catalog API. This flag keeps them out of `GET /api/v1/branches`.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('exclude_from_showcase')->default(false)->after('moq_sync');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('exclude_from_showcase');
        });
    }
};
