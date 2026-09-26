<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Money a tenant has paid the installation owner (AMC, setup, other).
     * renewed_from/renewed_to record how an AMC payment moved the tenant's
     * renewal date, so deleting the payment can put it back.
     */
    public function up(): void
    {
        Schema::create('tenant_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('paid_on');
            $table->string('type', 20)->default('amc');
            $table->decimal('amount', 12, 2);
            $table->string('method', 30)->nullable();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->date('renewed_from')->nullable();
            $table->date('renewed_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payments');
    }
};
