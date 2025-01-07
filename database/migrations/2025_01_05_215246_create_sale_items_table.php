<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->references('id')->on('sales');
            $table->unsignedBigInteger('inventory_id')->references('id')->on('inventories');
            $table->unsignedBigInteger('product_id')->references('id')->on('products');

            $table->decimal('unit_price', 8, 2);
            $table->decimal('quantity', 8, 3);
            $table->decimal('gross_amount', 8, 2)->storedAs('unit_price * quantity');

            $table->decimal('discount', 8, 2);
            $table->decimal('net_amount', 8, 2)->storedAs('gross_amount - discount');

            $table->decimal('tax', 8, 2);
            $table->decimal('tax_amount', 8, 2)->storedAs('(net_amount * tax)/100');

            $table->decimal('total', 8, 2)->storedAs('net_amount + tax_amount');

            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
