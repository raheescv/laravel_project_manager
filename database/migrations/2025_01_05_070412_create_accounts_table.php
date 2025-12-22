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

            $table->enum('account_type', array_keys(accountTypes()));
            $table->unsignedBigInteger('customer_type_id')->nullable()->references('id')->on('customer_types');
            $table->string('name', 100);
            $table->string('alias_name')->nullable();
            $table->string('mobile', 15)->nullable();

            $table->unique(['account_type', 'name', 'mobile'], 'unique_account_type_mobile_name');

            $table->string('model', 30)->nullable();

            $table->string('email', 50)->nullable();

            $table->string('description')->nullable();

            $table->boolean('is_locked')->default(0);

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
