<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('tailoring_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unsignedBigInteger('tailoring_order_id');
            $table->foreign('tailoring_order_id')->references('id')->on('tailoring_orders')->onDelete('cascade');
            $table->integer('item_no');

            // Category & Model
            $table->unsignedBigInteger('tailoring_category_id')->nullable();
            $table->foreign('tailoring_category_id')->references('id')->on('tailoring_categories')->onDelete('set null');
            $table->unsignedBigInteger('tailoring_category_model_id')->nullable();
            $table->foreign('tailoring_category_model_id')->references('id')->on('tailoring_category_models')->onDelete('set null');
            $table->unsignedBigInteger('tailoring_category_model_type_id')->nullable();
            $table->foreign('tailoring_category_model_type_id')->references('id')->on('tailoring_category_model_types')->onDelete('set null');
            // Product Information
            $table->unsignedBigInteger('inventory_id')->nullable();
            $table->foreign('inventory_id')->references('id')->on('inventories')->nullOnDelete();
            $table->index('inventory_id');
            $table->unsignedBigInteger('product_id')->nullable()->references('id')->on('products');
            $table->string('product_name');
            $table->string('product_color')->nullable();
            $table->foreignId('unit_id')->default(1)->constrained('units');
            $table->decimal('quantity', 8, 3);
            $table->decimal('quantity_per_item', 8, 3);
            $table->decimal('unit_price', 16, 2);
            $table->decimal('stitch_rate', 16, 2)->default(0);
            $table->decimal('total_stitch_rate', 16, 2)->storedAs('stitch_rate * quantity');
            $table->decimal('gross_amount', 16, 2)->storedAs('unit_price * quantity * quantity_per_item + total_stitch_rate');
            $table->decimal('discount', 16, 2)->default(0);
            $table->decimal('net_amount', 16, 2)->storedAs('gross_amount - discount');
            $table->decimal('tax', 16, 2)->default(0);
            $table->decimal('tax_amount', 16, 2)->storedAs('(net_amount * tax) / 100');
            $table->decimal('total', 16, 2)->storedAs('net_amount + tax_amount');

            // Job Completion Fields
            $table->decimal('tailor_total_commission', 16, 2)->default(0);

            $table->decimal('used_quantity', 8, 3)->default(0);
            $table->decimal('wastage', 8, 3)->default(0);
            $table->decimal('total_quantity_used', 16, 2)->storedAs('used_quantity + wastage');

            $table->date('item_completion_date')->nullable();

            $table->unsignedInteger('completed_quantity')->default(0);
            $table->unsignedInteger('pending_quantity')->storedAs('CAST(GREATEST(quantity - completed_quantity, 0) AS UNSIGNED)');
            $table->unsignedInteger('delivered_quantity')->default(0);

            $table->enum('completion_status', array_keys(tailoringOrderItemCompletionStatuses()))->storedAs("IF(completed_quantity >= quantity, 'completed', IF(completed_quantity > 0, 'partially completed', 'not completed'))");
            $table->enum('delivery_status', array_keys(tailoringOrderItemDeliveryStatuses()))->storedAs("IF(delivered_quantity >= quantity, 'delivered', IF(delivered_quantity > 0, 'partially delivered', 'not delivered'))");

            // Derived status: prefer explicit CASE expression for readability
            $statusOptions = array_keys(tailoringOrderItemStatuses());
            $statusExpression = "CASE
                WHEN delivery_status = 'delivered' THEN 'delivered'
                WHEN completion_status = 'completed' THEN 'completed'
                WHEN completion_status = 'partially completed' OR delivery_status = 'partially delivered' THEN 'partially completed'
                ELSE 'pending'
            END";

            $table->enum('status', $statusOptions)->storedAs($statusExpression);

            // Additional
            $table->text('tailoring_notes')->nullable();

            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'tailoring_order_id']);
            $table->index(['tenant_id', 'tailoring_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailoring_order_items');
    }
};
