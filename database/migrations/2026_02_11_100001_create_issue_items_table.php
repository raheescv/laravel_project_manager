<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('issue_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unsignedBigInteger('issue_id');
            $table->foreign('issue_id')->references('id')->on('issues')->onDelete('cascade');
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unsignedBigInteger('inventory_id')->nullable();
            $table->foreign('inventory_id')->references('id')->on('inventories')->nullOnDelete();

            $table->unsignedBigInteger('source_issue_item_id')->nullable();
            $table->unsignedInteger('source_item_order')->nullable();
            $table->foreign('source_issue_item_id')->references('id')->on('issue_items')->nullOnDelete();
            $table->index('source_issue_item_id');
            $table->index('source_item_order');

            $table->decimal('quantity_in', 16, 2)->default(0);
            $table->decimal('quantity_out', 16, 2)->default(0);
            $table->index('inventory_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_items');
    }
};
