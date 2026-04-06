<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('property_group_id')->nullable();
            $table->unsignedBigInteger('property_building_id')->nullable();
            $table->unsignedBigInteger('property_type_id')->nullable();
            $table->unsignedBigInteger('rent_out_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('priority')->default('low');
            $table->string('segment')->nullable();
            $table->string('contact_no')->nullable();
            $table->text('remark')->nullable();
            $table->text('company_remark')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id']);
            $table->index('property_id');
            $table->index('account_id');
            $table->index('status');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
