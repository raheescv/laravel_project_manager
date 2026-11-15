<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The branch a student belongs to: picked as the school on the public
 * registration form, and where the student's canteen pre-orders are sold.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('student_details', function (Blueprint $table): void {
            $table->unsignedBigInteger('branch_id')->nullable()->after('school');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_details', function (Blueprint $table): void {
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
