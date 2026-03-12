<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('rent_out_utility_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('rent_out_id')->constrained('rent_outs')->cascadeOnDelete();
            $table->foreignId('utility_id')->constrained('utilities')->cascadeOnDelete();
            $table->date('date');
            $table->date('paid_date')->nullable();
            $table->decimal('amount', 16, 2)->default(0);
            $table->decimal('paid', 16, 2)->default(0);
            $table->decimal('balance', 16, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->string('payment_mode')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'rent_out_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_out_utility_terms');
    }
};
