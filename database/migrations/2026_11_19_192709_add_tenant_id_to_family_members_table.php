<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * family_members had no tenant link, so the family tree page showed every
 * tenant's members. Nullable because existing rows have no known owner;
 * they stop appearing for every tenant until assigned.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
