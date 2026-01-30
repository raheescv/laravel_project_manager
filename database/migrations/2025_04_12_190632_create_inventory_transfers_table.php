<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->date('date');
            $table->unsignedBigInteger('branch_id')->references('id')->on('branches');
            $table->unsignedBigInteger('from_branch_id')->references('id')->on('branches');
            $table->unsignedBigInteger('to_branch_id')->references('id')->on('branches');
            $table->string('description')->nullable();
            $table->enum('status', pendingCompletedStatuses())->default('pending');
            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('approved_by')->nullable()->references('id')->on('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('signature')->nullable();
            $table->unsignedBigInteger('updated_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
    }
};
