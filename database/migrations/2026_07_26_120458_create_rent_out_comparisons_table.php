<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rent_out_comparisons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('rent_out_id')->unique();
            $table->string('agreement_type', 30)->index();
            $table->string('category', 80)->index();
            $table->string('status', 40)->nullable()->index();
            $table->boolean('is_booking')->default(false)->index();
            $table->boolean('exists_old')->default(false);
            $table->boolean('exists_new')->default(false);
            $table->boolean('matches')->default(false)->index();
            $table->unsignedInteger('difference_count')->default(0);
            $table->text('old_url');
            $table->text('new_url');
            $table->json('payload');
            $table->timestamp('compared_at')->index();
            $table->timestamp('verified_at')->nullable()->index();
            $table->unsignedBigInteger('verified_by')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_out_comparisons');
    }
};
