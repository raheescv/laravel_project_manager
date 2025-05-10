<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->references('id')->on('sales');
            $table->unsignedBigInteger('employee_id')->references('id')->on('users');

            $table->unsignedBigInteger('inventory_id')->references('id')->on('inventories');
            $table->unsignedBigInteger('product_id')->references('id')->on('products');
            $table->unsignedBigInteger('sale_combo_offer_id')->nullable();

            $table->decimal('unit_price', 16, 2);
            $table->decimal('quantity', 8, 3);
            $table->decimal('gross_amount', 16, 2)->storedAs('unit_price * quantity');

            $table->decimal('discount', 16, 2);
            $table->decimal('net_amount', 16, 2)->storedAs('gross_amount - discount');

            $table->decimal('tax', 16, 2);
            $table->decimal('tax_amount', 16, 2)->storedAs('(net_amount * tax)/100');

            $table->decimal('total', 16, 2)->storedAs('net_amount + tax_amount');

            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');

            $table->softDeletes();
            $table->timestamps();

            $table->index('inventory_id');
            $table->index('product_id');
            $table->index(['product_id', 'employee_id'], 'product_id_employee_id_index');
            $table->index('employee_id');
            $table->index('sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
