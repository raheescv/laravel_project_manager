<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('grns', function (Blueprint $table) {
            $table->id();
            $table->string('grn_no', 50)->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('local_purchase_order_id');
            $table->date('date');
            $table->unsignedBigInteger('created_by');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('decision_by')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('branch_id');
            $table->index('local_purchase_order_id');
            $table->index('created_by');
            $table->index('date');
            $table->index('status');

            $table->foreign('local_purchase_order_id')
                ->references('id')
                ->on('local_purchase_orders')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grns');
    }
};
