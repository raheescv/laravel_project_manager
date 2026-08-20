<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * An appointment gains a finish time.
     *
     * The customer now says when they arrive AND when they must leave, so the
     * length stops being one global number the whole diary pretends to keep. A
     * customer who asks for 90 minutes gets 90 minutes on the salesman's
     * calendar, and the overlap check has something real to compare against.
     *
     * Nullable so appointments booked before this — and any awaiting a slot —
     * stay valid; readers fall back to the configured slot length.
     *
     * The pa_active_slot_unique index is untouched: it keys on the START, which
     * is still the thing two customers can collide on to the second. Overlap of
     * two different starts is checked in BookAction inside the row lock.
     */
    public function up(): void
    {
        Schema::table('property_appointments', function (Blueprint $table): void {
            $table->dateTime('ends_at')->nullable()->after('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('property_appointments', function (Blueprint $table): void {
            $table->dropColumn('ends_at');
        });
    }
};
