<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id'], 'user_tenant_id_index');
            $table->enum('type', ['user', 'employee'])->default('user');
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('email');
            $table->string('mobile')->nullable();

            $table->boolean('is_admin')->default(0);
            $table->unsignedBigInteger('default_branch_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->foreign('designation_id')->references('id')->on('designations')->onDelete('set null');

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('pin')->nullable();

            $table->date('dob')->nullable();
            $table->date('doj')->nullable();

            $table->string('place')->nullable();
            $table->string('nationality')->nullable();

            $table->decimal('allowance', 10, 2)->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->decimal('hra', 10, 2)->nullable();
            $table->decimal('max_discount_per_sale', 5, 2)->default(100)->nullable();

            $table->boolean('is_locked')->default(0);
            $table->boolean('is_active')->default(1);
            $table->boolean('is_whatsapp_enabled')->default(0);
            $table->boolean('is_telegram_enabled')->default(false);
            $table->string('telegram_chat_id')->nullable();
            $table->string('second_reference_no')->nullable();
            $table->unique(['tenant_id', 'email']);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
