<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            $table->string('code', 24);
            $table->string('title', 120);
            $table->string('title_arabic', 120)->nullable();

            $table->enum('discount_type', ['percent', 'fixed']);
            $table->decimal('discount_value', 16, 2);
            $table->decimal('max_discount', 16, 2)->nullable();
            $table->decimal('min_bag_total', 16, 2)->default(0);

            $table->enum('applies_to', ['all', 'brand', 'category', 'product'])->default('all');
            $table->json('applies_to_ids')->nullable();

            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();

            $table->boolean('is_searchable')->default(true);
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
