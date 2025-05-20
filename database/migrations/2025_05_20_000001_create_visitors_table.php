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
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('url');
            $table->timestamp('visited_at');
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->timestamps();

            $table->index(['visited_at', 'ip_address']);
            $table->index(['visited_at', 'user_id']);
            $table->index('device_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
