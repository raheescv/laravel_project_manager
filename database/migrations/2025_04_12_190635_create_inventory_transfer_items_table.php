<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('inventory_transfer_id');
            $table->unsignedBigInteger('product_id')->nullable()->references('id')->on('products');
            $table->unsignedBigInteger('inventory_id')->references('id')->on('inventories');
            $table->float('quantity', 16, 2)->default(0)->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_items');
    }
};
