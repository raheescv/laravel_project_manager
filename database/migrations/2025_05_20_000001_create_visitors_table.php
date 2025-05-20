<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('url');
            $table->timestamp('visited_at');
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();

            // Optimized indexes for common queries
            $table->index('visited_at');
            $table->index(['visited_at', 'user_id']);
            $table->index(['visited_at', 'device_type']);
            $table->index(['visited_at', 'url']);
            $table->index(['ip_address', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
