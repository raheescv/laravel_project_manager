<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Date-specific blocks that override the weekly availability rules.
     * A row with null start_time/end_time blocks the whole day.
     */
    public function up(): void
    {
        Schema::create('property_appointment_time_offs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('reason', 120)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'user_id', 'date'], 'pa_timeoff_tenant_user_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_appointment_time_offs');
    }
};
