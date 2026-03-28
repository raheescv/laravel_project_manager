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
        Schema::create('local_purchase_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('local_purchase_order_id')->constrained('local_purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->decimal('quantity', 10, 3);
            $table->decimal('rate', 10, 2);
            $table->decimal('total', 16, 4)->storedAs('quantity * rate');

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('local_purchase_order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_purchase_order_items');
    }
};
