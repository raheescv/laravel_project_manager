<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id'], 'product_unit_tenant_id_index');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('sub_unit_id');
            $table->double('conversion_factor', 8, 3);
            $table->string('barcode');
            $table->unique(['product_id', 'sub_unit_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
