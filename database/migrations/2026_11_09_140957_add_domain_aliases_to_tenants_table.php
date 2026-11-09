<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Extra hosts that open the same tenant as its subdomain and custom domain.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->json('domain_aliases')->nullable()->after('domain');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('domain_aliases');
        });
    }
};
