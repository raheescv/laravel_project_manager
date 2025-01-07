<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('product_id');
            $table->double('quantity_in', 8, 3);
            $table->double('quantity_out', 8, 3);
            $table->double('balance', 8, 3);
            $table->string('barcode');
            $table->string('batch');

            $table->float('cost', 8, 2)->default(0);

            $table->string('model', 30)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('remarks')->nullable();

            $table->unsignedBigInteger('user_id');
            $table->string('user_name');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};
