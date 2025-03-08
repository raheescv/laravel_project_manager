<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->references('id')->on('purchases');
            $table->unsignedBigInteger('payment_method_id')->references('id')->on('accounts');
            $table->date('date');
            $table->decimal('amount', 16, 2);
            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
