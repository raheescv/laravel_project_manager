<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('rent_out_securities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('rent_out_id')->constrained('rent_outs')->cascadeOnDelete();
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('payment_mode')->default('cash'); // cash, cheque, pos, bank_transfer
            $table->string('bank_name')->nullable();
            $table->string('cheque_no')->nullable();
            $table->string('status')->default('pending'); // pending, collected, returned, adjusted
            $table->string('type')->default('deposit'); // deposit, guarantee
            $table->date('due_date')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'rent_out_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_out_securities');
    }
};
