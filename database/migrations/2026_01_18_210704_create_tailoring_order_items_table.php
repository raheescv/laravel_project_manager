<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('tailoring_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unsignedBigInteger('tailoring_order_id');
            $table->foreign('tailoring_order_id')->references('id')->on('tailoring_orders')->onDelete('cascade');
            $table->integer('item_no');
            
            // Category & Model
            $table->unsignedBigInteger('tailoring_category_id')->nullable();
            $table->foreign('tailoring_category_id')->references('id')->on('tailoring_categories')->onDelete('set null');
            $table->unsignedBigInteger('tailoring_category_model_id')->nullable();
            $table->foreign('tailoring_category_model_id')->references('id')->on('tailoring_category_models')->onDelete('set null');
            
            // Product Information
            $table->unsignedBigInteger('product_id')->nullable()->references('id')->on('products');
            $table->string('product_name');
            $table->string('product_color')->nullable();
            $table->foreignId('unit_id')->default(1)->constrained('units');
            $table->decimal('quantity', 8, 3);
            $table->decimal('unit_price', 16, 2);
            $table->decimal('stitch_rate', 16, 2)->default(0);
            $table->decimal('gross_amount', 16, 2);
            $table->decimal('discount', 16, 2)->default(0);
            $table->decimal('net_amount', 16, 2);
            $table->decimal('tax', 16, 2)->default(0);
            $table->decimal('tax_amount', 16, 2);
            $table->decimal('total', 16, 2);
            
            // Basic Measurements (Left Column)
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('shoulder', 8, 2)->nullable();
            $table->decimal('sleeve', 8, 2)->nullable();
            $table->decimal('chest', 8, 2)->nullable();
            $table->string('stomach')->nullable();
            $table->decimal('sl_chest', 8, 2)->nullable();
            $table->decimal('sl_so', 8, 2)->nullable();
            $table->decimal('neck', 8, 2)->nullable();
            $table->string('bottom')->nullable();
            $table->string('mar_size')->nullable();
            $table->string('mar_model')->nullable();
            
            // Cuff Details
            $table->string('cuff')->nullable();
            $table->string('cuff_size')->nullable();
            $table->string('cuff_cloth')->nullable();
            $table->string('cuff_model')->nullable();
            
            // Additional Measurements
            $table->string('neck_d_button')->nullable();
            $table->string('side_pt_size')->nullable();
            
            // Collar Details (Right Column)
            $table->string('collar')->nullable();
            $table->string('collar_size')->nullable();
            $table->string('collar_cloth')->nullable();
            $table->string('collar_model')->nullable();
            
            // Additional Styling
            $table->string('regal_size')->nullable();
            $table->string('knee_loose')->nullable();
            $table->string('fp_down')->nullable();
            $table->string('fp_model')->nullable();
            $table->string('fp_size')->nullable();
            $table->string('pen')->nullable();
            $table->string('side_pt_model')->nullable();
            $table->string('stitching')->nullable();
            $table->string('button')->nullable();
            $table->string('button_no')->nullable();
            $table->enum('mobile_pocket', ['Yes', 'No'])->default('No');
            
            // Job Completion Fields
            $table->unsignedBigInteger('tailor_id')->nullable()->references('id')->on('users');
            $table->decimal('tailor_commission', 10, 2)->default(0);
            $table->decimal('tailor_total_commission', 10, 2)->default(0);
            $table->decimal('used_quantity', 8, 3)->default(0);
            $table->decimal('wastage', 8, 3)->default(0);
            $table->decimal('total_quantity_used', 8, 3)->default(0);
            $table->date('item_completion_date')->nullable();
            $table->boolean('is_selected_for_completion')->default(false);
            
            // Additional
            $table->text('tailoring_notes')->nullable();
            $table->unsignedBigInteger('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable()->references('id')->on('users');
            $table->unsignedBigInteger('deleted_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['tenant_id', 'tailoring_order_id']);
            $table->index(['tenant_id', 'tailoring_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailoring_order_items');
    }
};
