<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sale_day_sessions')) {
            Schema::create('sale_day_sessions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('branch_id');
                $table->unsignedBigInteger('opened_by')->nullable();
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->dateTime('opened_at');
                $table->dateTime('closed_at')->nullable();
                $table->decimal('opening_amount', 15, 2)->default(0);
                $table->decimal('closing_amount', 15, 2)->nullable();
                $table->decimal('expected_amount', 15, 2)->nullable();
                $table->decimal('difference_amount', 15, 2)->nullable();
                $table->enum('status', ['open', 'closed'])->default('open'); // open, closed
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                $table->foreign('opened_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_day_sessions');
    }
};
