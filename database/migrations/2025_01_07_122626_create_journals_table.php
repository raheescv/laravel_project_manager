<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('description');
            $table->string('remarks')->nullable();
            $table->string('reference_number', 50)->nullable();

            $table->string('model', 50)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();

            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
