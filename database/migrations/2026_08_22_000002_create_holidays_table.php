<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * The company holiday calendar — the specific DATES the business closes.
     *
     * Settings -> Working Day answers "which days of the week do we open"; this
     * table answers "and which particular dates are we shut anyway". The two are
     * deliberately separate: a weekly pattern cannot express Eid, and a list of
     * dates cannot express "we never open on Fridays".
     *
     * One row is one date. A holiday that runs for several days is entered as a
     * row per day — which keeps every read a plain date lookup, and keeps the
     * list honest about how many days are actually closed.
     *
     * Global by design. A holiday is a company closure, so it applies to every
     * branch and every employee at once — there is no branch_id or user_id here.
     * An individual employee being away is time off
     * (property_appointment_time_offs), which is a different thing.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('name');
            $table->date('date');
            // Repeats every year on the same month/day. Opt-in, because the
            // Islamic calendar holidays move and must be entered per year.
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
