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
        Schema::create('unique_no_counters', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->string('year', 5);
            $table->string('branch_code', 5);
            $table->string('segment', 20);
            $table->integer('number')->default(0);
            $table->primary(['tenant_id', 'year', 'branch_code', 'segment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unique_no_counters');
    }
};
