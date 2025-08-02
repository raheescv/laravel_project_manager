<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'moq_sync')) {
                $table->boolean('moq_sync')->default(false)->after('mobile');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'moq_sync')) {
                $table->dropColumn('moq_sync');
            }
        });
    }
};
