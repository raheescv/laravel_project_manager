<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->references('id')->on('appointments');
            $table->unsignedBigInteger('service_id')->references('id')->on('products');
            $table->unsignedBigInteger('employee_id')->references('id')->on('users');
            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable()->references('id')->on('users');

            $table->unique(['appointment_id', 'service_id', 'employee_id'], 'appointment_service_employee_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_items');
    }
};
