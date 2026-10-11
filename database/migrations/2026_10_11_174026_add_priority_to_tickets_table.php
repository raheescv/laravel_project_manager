<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Statuses become tenant-defined board columns (ticket_statuses.key), so the
     * enum turns into a plain string; priority is a fixed, optional level.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('status', 50)->default('open')->change();
            $table->string('priority', 20)->nullable()->after('status');
            $table->index(['tenant_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'priority']);
            $table->dropColumn('priority');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open')->change();
        });
    }
};
