<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * A property booking made by the customer from a signed public link.
     *
     * The booking stores both ends of the visit: the customer says when they
     * arrive (scheduled_at) AND when they must leave (ends_at), so the length
     * is not one global number the whole diary pretends to keep — a customer
     * who asks for 90 minutes gets 90 minutes on the salesman's calendar and
     * the overlap check has something real to compare against.
     *
     * ends_at is nullable: an appointment still awaiting a slot has neither
     * end, and readers fall back to the configured slot length.
     *
     * salesman_id is COPIED from rent_outs.salesman_id when the link is sent,
     * not joined live: reassigning the agreement later must not silently move
     * every past booking onto a different person's calendar.
     */
    public function up(): void
    {
        Schema::create('property_appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('reference_no', 30);
            $table->unsignedBigInteger('rent_out_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('salesman_id');

            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->enum('status', ['awaiting', 'scheduled', 'completed', 'cancelled', 'no_show'])->default('awaiting');

            $table->uuid('token');
            $table->dateTime('token_expires_at')->nullable();
            $table->dateTime('link_sent_at')->nullable();
            $table->dateTime('link_opened_at')->nullable();
            $table->unsignedInteger('link_opened_count')->default(0);

            $table->dateTime('booked_at')->nullable();
            $table->enum('booked_by', ['customer', 'staff'])->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->string('customer_timezone', 64)->nullable();

            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->string('cancel_reason', 255)->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'reference_no'], 'pa_tenant_reference_unique');
            $table->unique('token', 'pa_token_unique');
            $table->index(['tenant_id', 'rent_out_id'], 'pa_tenant_rentout_idx');
            $table->index(['tenant_id', 'salesman_id', 'scheduled_at'], 'pa_tenant_salesman_sched_idx');
            $table->index(['tenant_id', 'status'], 'pa_tenant_status_idx');
        });

        // ── The no-double-booking guarantee, enforced by the database ────────
        //
        // MySQL has no partial indexes, so the uniqueness is carried by a
        // STORED generated column that is NULL for anything that does not hold
        // a slot (awaiting / cancelled / no-show / soft-deleted). NULLs never
        // collide in a MySQL unique index, so cancelled bookings correctly free
        // their slot for re-booking while live ones cannot be double-taken.
        //
        // Two concurrent public requests for the same slot therefore end with
        // one write succeeding and the other failing on this constraint —
        // which BookAction catches and turns into "that slot was just taken".
        DB::statement("
            ALTER TABLE property_appointments
            ADD COLUMN active_slot_key VARCHAR(64)
            GENERATED ALWAYS AS (
                CASE
                    WHEN status IN ('scheduled', 'completed') AND deleted_at IS NULL AND scheduled_at IS NOT NULL
                    THEN CONCAT(salesman_id, '@', scheduled_at)
                    ELSE NULL
                END
            ) STORED
        ");

        DB::statement('
            ALTER TABLE property_appointments
            ADD UNIQUE INDEX pa_active_slot_unique (tenant_id, active_slot_key)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('property_appointments');
    }
};
