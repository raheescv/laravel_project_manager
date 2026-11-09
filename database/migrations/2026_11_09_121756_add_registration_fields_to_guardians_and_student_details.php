<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The two facts the registration form collects that had no column: a parent's QID
 * and which school a student attends.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->string('id_no', 30)->nullable()->after('email');
        });

        Schema::table('student_details', function (Blueprint $table) {
            $table->string('school', 100)->nullable()->after('admission_no');
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropColumn('id_no');
        });

        Schema::table('student_details', function (Blueprint $table) {
            $table->dropColumn('school');
        });
    }
};
