<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('combo_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('count');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('sale_combo_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('combo_offer_id');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('combo_offer_id')->references('id')->on('combo_offers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_combo_offers');
        Schema::dropIfExists('combo_offers');
    }
};
