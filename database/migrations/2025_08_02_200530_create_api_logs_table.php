<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('method', 8)->default('POST');
            $table->text('request')->nullable();
            $table->text('response')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('success');
            $table->text('description')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('token')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->timestamps();

            $table->index(['endpoint', 'status']);
            $table->index(['user_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
