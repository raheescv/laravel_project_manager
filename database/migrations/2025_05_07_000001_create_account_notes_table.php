<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('account_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->references('id')->on('accounts');
            $table->text('note');
            $table->enum('type', array_keys(noteTypes()))->default('general');
            $table->date('follow_up_date')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index('account_id');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_notes');
    }
};
