<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('product_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->enum('type', ['product', 'service'])->default('product');
            $table->index('tenant_id');

            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'disabled'])->default('active');

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');

            $table->timestamps();
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_offers');
    }
};
