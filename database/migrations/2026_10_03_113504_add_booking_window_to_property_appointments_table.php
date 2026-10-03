<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * The dates the customer may pick from, chosen per appointment.
     *
     * Both nullable: an appointment without them keeps the configured rolling
     * window (today + appointment_window_days), so existing links behave as
     * they always did.
     */
    public function up(): void
    {
        Schema::table('property_appointments', function (Blueprint $table): void {
            $table->date('available_from')->nullable()->after('token_expires_at');
            $table->date('available_until')->nullable()->after('available_from');
        });
    }

    public function down(): void
    {
        Schema::table('property_appointments', function (Blueprint $table): void {
            $table->dropColumn(['available_from', 'available_until']);
        });
    }
};
