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
        Schema::create('stock_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->index('branch_id');
            $table->string('title');
            $table->date('date');
            $table->text('description')->nullable();
            $table->text('signature')->nullable();
            $table->unsignedBigInteger('signed_by')->nullable();
            $table->foreign('signed_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('signed_by');
            $table->timestamp('signed_at')->nullable();

            $table->enum('status', array_keys(stockCheckStatuses()))->default('pending');

            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('updated_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_checks');
    }
};
