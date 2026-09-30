<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('api_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('api_logs', 'app_version')) {
                $table->string('app_version', 60)->nullable()->after('service_name');
                $table->string('app_platform', 60)->nullable()->after('app_version');
                $table->index('app_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_logs', function (Blueprint $table) {
            if (Schema::hasColumn('api_logs', 'app_version')) {
                $table->dropIndex(['app_version']);
                $table->dropColumn(['app_version', 'app_platform']);
            }
        });
    }
};
