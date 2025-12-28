<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_category_id')->references('id')->on('package_categories');
            $table->unsignedBigInteger('account_id')->references('id')->on('accounts');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('remarks')->nullable();
            $table->decimal('amount', 16, 2)->default(0);
            $table->decimal('paid', 16, 2)->default(0);
            $table->decimal('balance', 16, 2)->storedAs('amount - paid');
            $table->enum('status', array_keys(packageStatuses()))->default('in_progress');
            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable()->references('id')->on('users');
            $table->timestamps();

            $table->index('package_category_id');
            $table->index('account_id');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
