<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('universal_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unsignedBigInteger('base_unit_id');
            $table->foreign('base_unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->unsignedBigInteger('sub_unit_id');
            $table->foreign('sub_unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->double('conversion_factor', 12, 4)->comment('1 base_unit = conversion_factor × sub_unit (e.g. 1 L = 1000 ml)');
            $table->unique(['tenant_id', 'base_unit_id', 'sub_unit_id'],'uuc_tenant_base_sub_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universal_unit_conversions');
    }
};
