<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (! Schema::hasColumn('configurations', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->index('tenant_id');
                // Remove unique constraint on key, make it unique per tenant
                $table->dropUnique(['key']);
                $table->unique(['tenant_id', 'key']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (Schema::hasColumn('configurations', 'tenant_id')) {
                $table->dropUnique(['tenant_id', 'key']);
                $table->unique(['key']);
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};
