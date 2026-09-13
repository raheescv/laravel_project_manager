<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online checkouts from the public storefront (showcase website).
 *
 * A row is written the moment a customer presses Pay, BEFORE any money moves.
 * The Tap charge only carries an amount, so the bag itself — lines, shop,
 * customer, delivery address — has to live somewhere until the payment settles.
 * Once Tap reports the charge CAPTURED the row becomes a completed Sale and
 * `sale_id` points at it; a declined or abandoned payment leaves no sale behind.
 *
 * `reference` is the public handle the storefront polls with, so it is random
 * and unguessable rather than the auto-increment id.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_checkouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('reference', 40)->unique();
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();

            $table->string('fulfilment', 20);
            $table->string('customer_name', 100);
            $table->string('customer_mobile', 20);
            $table->string('customer_email', 150)->nullable();
            $table->text('address')->nullable();
            $table->json('items');

            $table->decimal('amount', 16, 2);
            $table->string('currency', 3);

            $table->string('gateway', 20)->default('tap');
            $table->string('gateway_charge_id', 64)->nullable();
            $table->string('gateway_status', 30)->nullable();
            $table->json('gateway_response')->nullable();

            $table->string('status', 20)->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'storefront_checkouts_tenant_status_index');
            $table->index('gateway_charge_id', 'storefront_checkouts_gateway_charge_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_checkouts');
    }
};
