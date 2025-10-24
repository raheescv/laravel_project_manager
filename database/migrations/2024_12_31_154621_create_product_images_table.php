<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->enum('method', ['normal', 'angle'])->default('normal');
            $table->integer('degree')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('name')->nullable();
            $table->string('size')->nullable();
            $table->string('type')->nullable();
            $table->string('path');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
