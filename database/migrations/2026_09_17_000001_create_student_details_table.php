<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The school-only facts about a student.
 *
 * A student IS an `accounts` row (model = student): name, mobile, email, dob,
 * id_no, nationality and photo already live there, and so does the ledger that
 * holds the card balance. This table carries only what an account has no column
 * for — admission number, class, and the NFC card read by QLOUD POS.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('student_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('account_id')->unique();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            $table->string('admission_no', 30);
            $table->string('gender', 10)->nullable();
            $table->string('grade', 30)->nullable();
            $table->string('section', 30)->nullable();
            $table->string('status', 20)->default('active');

            // The card's UID as the POS reads it: uppercase hex, no separators.
            $table->string('card_uid', 40)->nullable();
            $table->string('card_status', 20)->default('active');
            $table->timestamp('card_blocked_at')->nullable();
            $table->string('card_blocked_by_type', 20)->nullable();
            $table->unsignedBigInteger('card_blocked_by_id')->nullable();
            $table->string('card_block_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'admission_no'], 'student_details_tenant_admission_no_unique');
            $table->unique(['tenant_id', 'card_uid'], 'student_details_tenant_card_uid_unique');
            $table->index(['tenant_id', 'status', 'grade', 'section'], 'student_details_tenant_class_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_details');
    }
};
