<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('trading_orders', function (Blueprint $table) {
            $table->id();
            $table->string('symbol');
            $table->enum('type', ['BUY', 'SELL']);
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->decimal('stop_loss', 10, 2)->nullable();
            $table->decimal('take_profit', 10, 2)->nullable();
            $table->string('status')->default('OPEN'); // OPEN, CLOSED, CANCELED
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_orders');
    }
};
