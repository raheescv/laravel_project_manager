<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * The company-wide hours kept on each working day.
     *
     * Until now a working day only said WHETHER the business opens; the hours it
     * opens for lived in config/property_appointment.php, where a tenant could
     * not reach them. These columns make the timing part of the same setting, so
     * Settings -> Working Day is the single global answer to "when do we work",
     * and a salesman's own weekly availability is an override of it.
     *
     * Nullable on purpose: a null column means "never configured", and the
     * module default in config/property_appointment.php still answers for it.
     *
     * Slot length is deliberately NOT here: it is one number for the whole
     * application (config/property_appointment.php), not something a tenant
     * tunes per day.
     */
    public function up(): void
    {
        Schema::table('working_days', function (Blueprint $table): void {
            $table->time('start_time')->nullable()->after('is_working');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('working_days', function (Blueprint $table): void {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
