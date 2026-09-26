<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * The tenant's subscription: when it started, when it next renews, and the
     * AMC (annual maintenance contract) charge collected each cycle.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->date('started_on')->nullable()->after('is_active');
            $table->date('renews_on')->nullable()->after('started_on')->index();
            $table->decimal('amc_amount', 12, 2)->nullable()->after('renews_on');
            $table->string('amc_cycle', 20)->nullable()->after('amc_amount');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropIndex(['renews_on']);
            $table->dropColumn(['started_on', 'renews_on', 'amc_amount', 'amc_cycle']);
        });
    }
};
