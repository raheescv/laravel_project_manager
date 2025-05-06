<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->references('id')->on('accounts');
            $table->unsignedBigInteger('branch_id')->references('id')->on('branches');
            $table->string('color');
            $table->datetime('start_time');
            $table->datetime('end_time');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'start_time']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
