<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parent portal logins and the students each parent may see.
 *
 * A guardian is a login, not a ledger: they own no account and post nothing.
 * They are invited by the school (no self-registration) and authenticate on the
 * separate `parent` guard, so a parent session can never reach an admin route.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            $table->string('name', 100);
            $table->string('mobile', 20);
            $table->string('email', 150)->nullable();
            $table->string('password')->nullable();
            $table->string('status', 20)->default('active');

            // sha256 of the one-time set-password token; the raw token only ever travels in the invite link.
            $table->string('invite_token_hash', 64)->nullable();
            $table->timestamp('invite_expires_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'mobile'], 'guardians_tenant_mobile_index');
            $table->index(['tenant_id', 'email'], 'guardians_tenant_email_index');
            $table->index('invite_token_hash', 'guardians_invite_token_hash_index');
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guardian_id');
            $table->foreign('guardian_id')->references('id')->on('guardians')->onDelete('cascade');
            // The student's account.
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->string('relation', 20)->default('guardian');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['guardian_id', 'account_id'], 'guardian_student_unique');
            $table->index('account_id', 'guardian_student_account_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('guardians');
    }
};
