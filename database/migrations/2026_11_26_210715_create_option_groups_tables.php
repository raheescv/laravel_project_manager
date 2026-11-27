<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Option Groups (modifiers): reusable choices asked at the till for one item,
 * e.g. "Drink base" (pick 1: Red Bull, Soda), "Flavour" (pick 1) or "Tea
 * add-ons" (pick up to 3, each with its own price). They are not stock items
 * of their own (that is what variants are for), but an option may use a stock
 * item (one Red Bull can) and may open a follow-up group (Red Bull → Sugar-free).
 *
 *   option_groups          the reusable group and its pick rule
 *   option_group_items     its options: price, stock use, follow-up group
 *   product_option_groups  which groups a product asks, in order
 *   sale_item_options      what was picked on a sale line (a snapshot)
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('option_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->string('name', 100);
            $table->unsignedSmallInteger('min_select')->default(0);
            $table->unsignedSmallInteger('max_select')->nullable();
            $table->integer('sort')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'name'], 'option_groups_tenant_name_unique');
        });

        Schema::create('option_group_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->foreignId('option_group_id')->constrained('option_groups')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('price', 16, 4)->default(0);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->foreignId('next_group_id')->nullable()->constrained('option_groups')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->integer('sort')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['option_group_id', 'sort']);
        });

        Schema::create('product_option_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('option_group_id')->constrained('option_groups')->cascadeOnDelete();
            $table->integer('sort')->default(0);
            $table->unsignedSmallInteger('min_select')->nullable();
            $table->unsignedSmallInteger('max_select')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['product_id', 'option_group_id'], 'product_option_groups_unique');
        });

        Schema::create('sale_item_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('sale_item_options')->cascadeOnDelete();
            $table->foreignId('option_group_id')->nullable()->constrained('option_groups')->nullOnDelete();
            $table->foreignId('option_group_item_id')->nullable()->constrained('option_group_items')->nullOnDelete();
            $table->string('group_name', 100);
            $table->string('option_name', 100);
            $table->decimal('price', 16, 4)->default(0);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('stock_quantity', 15, 4)->default(0);
            $table->integer('sort')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['sale_item_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_item_options');
        Schema::dropIfExists('product_option_groups');
        Schema::dropIfExists('option_group_items');
        Schema::dropIfExists('option_groups');
    }
};
