<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('supply_request_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supply_request_id');
            $table->string('name');
            $table->string('path');
            $table->string('type')->nullable();
            $table->timestamps();

            $table->foreign('supply_request_id')->references('id')->on('supply_requests')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_request_images');
    }
};
