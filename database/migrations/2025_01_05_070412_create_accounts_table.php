<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');

            $table->enum('account_type', array_keys(accountTypes()));
            $table->unsignedBigInteger('customer_type_id')->nullable()->references('id')->on('customer_types');
            $table->unsignedBigInteger('account_category_id')->nullable()->references('id')->on('account_categories');
            $table->string('name', 100);
            $table->string('alias_name')->nullable();
            $table->string('slug')->nullable();
            $table->decimal('opening_debit', 10, 2)->default(0);
            $table->decimal('opening_credit', 10, 2)->default(0);
            $table->string('mobile', 15)->nullable();
            $table->string('whatsapp_mobile', 15)->nullable();

            $table->unique(['tenant_id', 'account_type', 'name', 'mobile'], 'account_tenant_account_type_mobile_name_index');

            $table->string('model', 30)->nullable();

            $table->string('email', 50)->nullable();

            $table->date('dob')->nullable();
            $table->string('id_no')->nullable();
            $table->string('nationality')->nullable();
            $table->string('company')->nullable();
            $table->unsignedInteger('credit_period_days')->nullable();

            $table->string('place')->nullable();
            $table->string('description')->nullable();

            $table->boolean('is_locked')->default(0);
            $table->string('second_reference_no')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('account_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
