<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id'], 'branch_tenant_id_index');
            $table->string('name');
            $table->string('code');
            $table->unique(['tenant_id', 'code']);
            $table->unique(['tenant_id', 'name']);
            $table->string('location')->nullable();
            $table->string('mobile', 15)->nullable();
            $table->boolean('moq_sync')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
