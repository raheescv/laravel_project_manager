<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bilingual clauses printed under the inventory table of a booking's Unit
 * Handover & Snagging checklist, edited on the booking's own Handover Terms tab.
 *
 * Per booking rather than per tenant: two handovers signed in the same month can
 * carry different warranty wording, the same way Agreement Points already do.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('rent_outs', function (Blueprint $table) {
            $table->json('handover_terms')->nullable()->after('reservation_fees_disclaimer_ar');
        });
    }

    public function down(): void
    {
        Schema::table('rent_outs', function (Blueprint $table) {
            $table->dropColumn('handover_terms');
        });
    }
};
