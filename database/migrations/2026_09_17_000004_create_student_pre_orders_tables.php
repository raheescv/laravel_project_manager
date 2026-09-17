<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canteen pre-orders parents set up in the parent portal.
 *
 * A pre-order is a list of items, not money: nothing is charged or reserved when
 * a parent orders. When the student's card is tapped at the till, QLOUD POS puts
 * that day's items in the cart and the ordinary card sale charges them.
 *
 * - type `day`: an order for one date. `skipped` means "nothing that day", which
 *   silences the weekly order on that date.
 * - type `weekly`: the standing order for the given weekdays (active / paused).
 *
 * A collection row is written when the till completes a sale for it, one per
 * order per date — so a weekly order is collected once each school day.
 *
 * `canteen_menus` is what a meal product serves on each weekday (the same every
 * week): courses (e.g. "Main dish", "Mini portions of 150 g") with one dish per
 * weekday. Display content only — the till still sells the meal product.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('student_pre_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            // The student's account.
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            // The parent who set it up last.
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->foreign('guardian_id')->references('id')->on('guardians')->nullOnDelete();

            $table->string('type', 10);
            $table->date('date')->nullable();
            // ISO weekdays (1 = Monday … 7 = Sunday) for a weekly order.
            $table->json('weekdays')->nullable();
            $table->string('status', 12)->default('active');
            $table->string('note', 200)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'account_id', 'type', 'date'], 'student_pre_orders_lookup_index');
        });

        Schema::create('student_pre_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('student_pre_order_id');
            $table->foreign('student_pre_order_id')->references('id')->on('student_pre_orders')->onDelete('cascade');
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unsignedTinyInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['student_pre_order_id', 'product_id'], 'student_pre_order_items_unique');
        });

        Schema::create('canteen_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            // The meal product parents order.
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            // [{name, note, dishes: {"<iso weekday>": {name, description}}}]
            $table->json('courses');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('product_id', 'canteen_menus_product_unique');
        });

        Schema::create('student_pre_order_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('student_pre_order_id');
            $table->foreign('student_pre_order_id')->references('id')->on('student_pre_orders')->onDelete('cascade');
            $table->unsignedBigInteger('account_id');
            $table->date('date');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['student_pre_order_id', 'date'], 'student_pre_order_collections_unique');
            $table->index(['tenant_id', 'account_id', 'date'], 'student_pre_order_collections_account_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_pre_order_collections');
        Schema::dropIfExists('canteen_menus');
        Schema::dropIfExists('student_pre_order_items');
        Schema::dropIfExists('student_pre_orders');
    }
};
