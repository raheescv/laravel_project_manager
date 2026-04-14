<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('supply_request_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supply_request_id');
            $table->string('note');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('supply_request_id')->references('id')->on('supply_requests')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_request_notes');
    }
};
