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
        Schema::create('stock_check_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_check_id');
            $table->foreign('stock_check_id')->references('id')->on('stock_checks')->onDelete('cascade');
            $table->index('stock_check_id');
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index('product_id');
            $table->decimal('physical_quantity', 16, 2);
            $table->decimal('recorded_quantity', 16, 2);
            $table->decimal('difference', 16, 2)->storedAs('physical_quantity - recorded_quantity');
            $table->enum('status', array_keys(stockCheckItemStatuses()))->default('pending');
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_check_items');
    }
};
