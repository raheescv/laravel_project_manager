<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Server-side state of a tenant's custom domain, written by the root-run
     * `tenant:server-sync` command (nginx site + certificate).
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('domain_status', 20)->nullable()->after('domain');
            $table->text('domain_error')->nullable()->after('domain_status');
            $table->timestamp('domain_synced_at')->nullable()->after('domain_error');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['domain_status', 'domain_error', 'domain_synced_at']);
        });
    }
};
