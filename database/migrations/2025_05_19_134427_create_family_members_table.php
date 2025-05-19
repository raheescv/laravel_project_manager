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
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('gender', ['male', 'female']);
            $table->string('image')->nullable();
            $table->date('date_of_birth')->nullable();

            // Family relationships
            $table->foreignId('father_id')->nullable()->constrained('family_members')->onDelete('set null');
            $table->foreignId('mother_id')->nullable()->constrained('family_members')->onDelete('set null');
            $table->foreignId('spouse_id')->nullable()->constrained('family_members')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
