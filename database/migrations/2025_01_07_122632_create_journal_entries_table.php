<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id')->references('id')->on('journals');
            $table->unsignedBigInteger('branch_id')->references('id')->on('branches');
            $table->unsignedBigInteger('account_id')->references('id')->on('accounts');
            $table->unsignedBigInteger('counter_account_id')->references('id')->on('accounts');

            $table->date('date');

            $table->decimal('debit', 16, 2)->default(0);
            $table->decimal('credit', 16, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->string('source')->nullable();
            $table->string('person_name')->nullable();
            $table->string('description')->nullable();
            $table->string('journal_remarks')->nullable();
            $table->string('reference_number')->nullable();

            $table->string('journal_model', 50)->nullable();
            $table->unsignedBigInteger('journal_model_id')->nullable();

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
        Schema::dropIfExists('journal_entries');
    }
};
