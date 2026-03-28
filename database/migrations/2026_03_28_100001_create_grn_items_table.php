<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('grn_id');
            $table->unsignedBigInteger('local_purchase_order_item_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 12, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('grn_id');
            $table->index('local_purchase_order_item_id');
            $table->index('product_id');

            $table->foreign('grn_id')->references('id')->on('grns')->onDelete('cascade');

            $table->foreign('local_purchase_order_item_id')->references('id')->on('local_purchase_order_items')->onDelete('restrict');

            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
    }
};
