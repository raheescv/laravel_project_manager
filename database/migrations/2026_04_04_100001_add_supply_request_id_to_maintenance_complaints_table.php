<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_complaints', function (Blueprint $table) {
            $table->unsignedBigInteger('supply_request_id')->nullable()->after('completed_at');
            $table->index('supply_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_complaints', function (Blueprint $table) {
            $table->dropIndex(['supply_request_id']);
            $table->dropColumn('supply_request_id');
        });
    }
};
