<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Slot length stops being data.
     *
     * It was stored per availability rule, which meant seven copies of the same
     * number per salesman and a UI asking a question nobody wanted to answer.
     * It is one application-wide value now — config('property_appointment
     * .default_availability.slot_interval_minutes') — read at the point the grid
     * is drawn and nowhere else.
     */
    public function up(): void
    {
        Schema::table('property_appointment_availabilities', function (Blueprint $table): void {
            $table->dropColumn('slot_interval_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('property_appointment_availabilities', function (Blueprint $table): void {
            $table->unsignedSmallInteger('slot_interval_minutes')->default(60)->after('end_time');
        });
    }
};
