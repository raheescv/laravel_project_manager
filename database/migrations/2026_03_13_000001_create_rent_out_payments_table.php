<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('rent_out_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('rent_out_id')->constrained('rent_outs')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('debit', 15, 2)->default(0);
            $table->unsignedBigInteger('account_id');
            $table->string('source'); // PaymentTerm, UtilityTerm, Service, ServiceCharge, Payout
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('group')->nullable();
            $table->string('category')->nullable();
            $table->string('payment_type')->nullable();
            $table->text('remark')->nullable();
            $table->string('voucher_no')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'rent_out_id']);
            $table->index(['tenant_id', 'source']);
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_out_payments');
    }
};
