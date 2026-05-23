<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('api_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('api_logs', 'service_name')) {
                $table->string('service_name')->nullable()->after('method');
                $table->index('service_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_logs', function (Blueprint $table) {
            if (Schema::hasColumn('api_logs', 'service_name')) {
                $table->dropIndex(['service_name']);
                $table->dropColumn('service_name');
            }
        });
    }
};
