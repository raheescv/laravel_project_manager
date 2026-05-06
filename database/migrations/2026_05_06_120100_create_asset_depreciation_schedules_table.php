<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('asset_depreciation_schedules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedInteger('period_no');
            $table->string('period_type', 20)->default('monthly');
            $table->date('schedule_date');
            $table->decimal('opening_book_value', 15, 2)->default(0);
            $table->decimal('depreciation_amount', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('closing_book_value', 15, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->timestamp('posted_at')->nullable();
            $table->text('posting_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('journal_id')->references('id')->on('journals')->nullOnDelete();
            $table->unique(['product_id', 'period_no']);
            $table->index(['status', 'schedule_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_schedules');
    }
};
