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
        Schema::create('physical_visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->string('name');
            $table->date('date_of_birth')->nullable();
            $table->string('id_card_type')->nullable();
            $table->string('id_card_number')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('purpose_of_visit');
            $table->foreignId('host_employee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('host_department')->nullable();
            $table->datetime('check_in_time');
            $table->datetime('check_out_time')->nullable();
            $table->string('status')->default('checked_in');
            $table->string('id_card_image_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_visitors');
    }
};
