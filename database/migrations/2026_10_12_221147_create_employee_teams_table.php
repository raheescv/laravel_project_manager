<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * An employee's team: the other employees whose leads they may see. Both
     * columns point at users — employees are users of type 'employee' here.
     */
    public function up(): void
    {
        Schema::create('employee_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_member_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'employee_id', 'team_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_teams');
    }
};
