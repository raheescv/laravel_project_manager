<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('product_id');
            $table->double('quantity', 8, 3);
            $table->string('barcode');
            $table->string('batch');

            $table->float('cost', 8, 2)->default(0);
            $table->decimal('total', 16, 2)->storedAs('(cost * quantity)');

            $table->string('model', 30)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('remarks')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');

            $table->index(['tenant_id', 'employee_id'], 'inventories_tenant_employee_id_index');
            $table->index(['tenant_id', 'branch_id'], 'inventories_tenant_branch_id_index');
            $table->index(['tenant_id', 'product_id'], 'inventories_tenant_product_id_index');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
