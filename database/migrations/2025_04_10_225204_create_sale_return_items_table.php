<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unsignedBigInteger('sale_return_id')->references('id')->on('sale_returns');
            $table->unsignedBigInteger('sale_item_id')->nullable()->references('id')->on('sale_items');

            $table->unsignedBigInteger('inventory_id')->references('id')->on('inventories');
            $table->unsignedBigInteger('product_id')->references('id')->on('products');
            $table->foreignId('unit_id')->default(1)->constrained('units');
            $table->decimal('conversion_factor', 15, 4)->default(1);
            $table->decimal('base_unit_quantity', 16, 4)->storedAs('quantity * conversion_factor');

            $table->unsignedBigInteger('employee_id')->references('id')->on('users');

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

            $table->index(['tenant_id', 'employee_id'], 'sale_return_items_tenant_employee_id_index');
            $table->index(['tenant_id', 'product_id'], 'sale_return_items_tenant_product_id_index');
            $table->index(['tenant_id', 'sale_return_id'], 'sale_return_items_tenant_sale_return_id_index');
            $table->index(['tenant_id', 'employee_id', 'product_id'], 'sale_return_items_tenant_employee_product_index');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
