<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['product', 'service'])->default('product');
            $table->string('name')->unique();
            $table->string('code');
            $table->string('name_arabic')->nullable();
            $table->string('thumbnail')->nullable();

            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('main_category_id');
            $table->unsignedBigInteger('sub_category_id')->nullable();

            $table->string('hsn_code')->nullable();
            $table->integer('tax')->default(0)->nullable();

            $table->text('description')->nullable();
            $table->boolean('is_selling')->default(true);

            $table->float('cost', 8, 2)->default(0);
            $table->float('mrp', 8, 2)->default(0);

            $table->integer('time')->default(0)->nullable();

            $table->string('barcode')->nullable();
            $table->string('pattern')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('model')->nullable();
            $table->string('brand')->nullable();
            $table->string('part_no')->nullable();

            $table->integer('min_stock')->default(0)->nullable();
            $table->integer('max_stock')->default(0)->nullable();
            $table->string('location')->nullable();
            $table->string('reorder_level')->nullable();
            $table->string('plu')->nullable();

            $table->enum('status', ['active', 'disabled'])->default('active');

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
