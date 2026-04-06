<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('property_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_contact_no')->nullable();
            $table->string('source')->nullable();
            $table->string('type')->default('Sales')->comment('Sales | Rentout');
            $table->unsignedBigInteger('property_group_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('users.id');
            $table->date('assign_date')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('nationality')->nullable();
            $table->string('location')->nullable();
            $table->date('meeting_date')->nullable();
            $table->time('meeting_time')->nullable();
            $table->dateTime('meeting_datetime')->storedAs('TIMESTAMP(meeting_date, meeting_time)')->nullable();
            $table->json('remarks')->nullable();
            $table->string('status', 30)->default('New Lead');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id']);
            $table->index('property_group_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_leads');
    }
};
