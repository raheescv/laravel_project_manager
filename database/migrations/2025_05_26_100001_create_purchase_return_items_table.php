<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_return_id')->references('id')->on('purchase_returns');
            $table->unsignedBigInteger('purchase_item_id')->nullable()->references('id')->on('purchase_items');

            $table->unsignedBigInteger('product_id')->references('id')->on('products');
            $table->foreignId('unit_id')->nullable()->constrained('units')->after('product_id');
            $table->decimal('unit_price', 16, 2);
            $table->decimal('quantity', 8, 3);
            $table->decimal('conversion_factor', 16, 2)->default(1);
            $table->decimal('base_unit_quantity', 16, 4)->storedAs('quantity * conversion_factor');

            $table->decimal('gross_amount', 16, 2)->storedAs('unit_price * quantity');

            $table->decimal('discount', 16, 2);
            $table->decimal('net_amount', 16, 2)->storedAs('gross_amount - discount');

            $table->decimal('tax', 16, 2);
            $table->decimal('tax_amount', 16, 2)->storedAs('(net_amount * tax)/100');

            $table->decimal('total', 16, 2)->storedAs('net_amount + tax_amount');

            $table->string('remark')->nullable();

            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
