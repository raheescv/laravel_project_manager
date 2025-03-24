<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('user_has_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('branch_id')->references('id')->on('branches');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_has_branches');
    }
};
