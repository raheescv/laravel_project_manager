<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            $table->string('key', 50);
            $table->string('name', 50);
            $table->string('color', 7);
            $table->string('icon', 40);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
            $table->index(['tenant_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_statuses');
    }
};
