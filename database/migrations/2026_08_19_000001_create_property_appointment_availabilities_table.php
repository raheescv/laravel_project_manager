<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Weekly recurring bookable hours for a salesman. The customer never picks
     * a salesman — the slots offered are derived from these rows for whoever
     * sits on rent_outs.salesman_id.
     */
    public function up(): void
    {
        Schema::create('property_appointment_availabilities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_interval_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'user_id', 'day_of_week'], 'pa_avail_tenant_user_dow_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_appointment_availabilities');
    }
};
