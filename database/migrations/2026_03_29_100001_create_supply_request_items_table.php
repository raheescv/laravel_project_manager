<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('supply_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supply_request_id');
            $table->unsignedBigInteger('branch_id')->nullable()->comment('Store/Branch');
            $table->unsignedBigInteger('product_id');
            $table->enum('mode', ['New', 'Damaged'])->default('New');
            $table->decimal('quantity', 16, 2)->default(1);
            $table->decimal('unit_price', 16, 2)->default(0);
            $table->decimal('total', 16, 4)->storedAs('quantity * unit_price');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('supply_request_id')->references('id')->on('supply_requests')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_request_items');
    }
};
